<?php
/**
 * RAG architecture with Ollama and FileSystemVectorStore
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\FileSystem\FileSystemVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

# Ollama with Llama3
$config = new OllamaConfig();
$config->model = 'llama3.2:latest';
$chat = new OllamaChat($config);

# Embedding
$embeddingGenerator = new OllamaEmbeddingGenerator($config);

# File system vector store
$vectorStorePath = __DIR__ . '/../../../data/vectordb.json';
$store = new FileSystemVectorStore(filepath: $vectorStorePath);

# RAG
$qa = new QuestionAnswering(
    $store,
    $embeddingGenerator,
    $chat
);

# Define custom instructions for the model
$customInstructions = <<<EOT
This model functions as a specialized customer service representative for handling client inquiries about a specific product. It is designed to adapt to 
provided datasets, including CSV and JSON files, to understand and answer queries accurately. Responses should be concise, straightforward, and directly 
address the query without unnecessary elaboration. The focus is on providing actionable solutions or explanations in simple terms. If a requested feature or functionality 
is not available in the product, the response should mention that it is not currently supported but can be implemented through customization. Always offer customization as an 
option at the end of such responses. The model maintains a professional, polite, and helpful tone in all interactions, and the answers which are provided by the model should be short 
and simple, avoiding excessive details. even if the user doesnt speak english the model must only speak in english and not any language


You are a specialized AI assistant for a handyman service platform. Your purpose is to provide accurate, concise, and helpful information about the platform's features, roles, and functionality.

HOW YOU SHOULD BEHAVE:
1. Be direct and concise in your answers
2. Only provide information that is explicitly mentioned in the context
3. If information is not in the context, clearly state "I don't know" or "This information is not available in the provided context"
4. Focus on factual information rather than opinions
5. Use simple, clear language that is easy to understand
6. When listing items, use bullet points or numbered lists for clarity

WHAT YOU SHOULD AVOID:
1. Making assumptions or providing information not in the context
2. Using overly technical language unless necessary
3. Providing lengthy explanations when short answers would suffice
4. Speculating about features or capabilities not mentioned
5. Using marketing language or making promotional statements
6. Providing personal opinions or recommendations

Your primary goal is to help users understand the platform's capabilities and limitations based solely on the information provided in the context.
EOT;

# Feedback mechanism
$feedbackFile = __DIR__ . '/../../../data/model_feedback.json';

# Function to save feedback
function saveFeedback($question, $originalAnswer, $correctedAnswer, $feedbackFile) {
    $feedback = [];
    
    // Load existing feedback if file exists
    if (file_exists($feedbackFile)) {
        $feedback = json_decode(file_get_contents($feedbackFile), true) ?: [];
    }
    
    // Add new feedback
    $feedback[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'question' => $question,
        'original_answer' => $originalAnswer,
        'corrected_answer' => $correctedAnswer
    ];
    
    // Save feedback
    file_put_contents($feedbackFile, json_encode($feedback, JSON_PRETTY_PRINT));
    echo "Feedback saved successfully!\n";
}

# Function to calculate similarity between two strings
function calculateSimilarity($str1, $str2) {
    // Convert to lowercase for case-insensitive comparison
    $str1 = strtolower($str1);
    $str2 = strtolower($str2);
    
    // Extract key terms (words)
    $words1 = array_filter(explode(' ', $str1), function($word) { return strlen($word) > 2; });
    $words2 = array_filter(explode(' ', $str2), function($word) { return strlen($word) > 2; });
    
    // Count common words
    $commonWords = array_intersect($words1, $words2);
    
    // Calculate similarity score
    $totalWords = count(array_unique(array_merge($words1, $words2)));
    if ($totalWords === 0) return 0;
    
    return count($commonWords) / $totalWords;
}

# Function to load feedback for a specific question
function loadFeedback($question, $feedbackFile) {
    if (!file_exists($feedbackFile)) {
        return null;
    }
    
    $feedback = json_decode(file_get_contents($feedbackFile), true) ?: [];
    
    // Find the most recent feedback for this question or similar questions
    $relevantFeedback = null;
    $highestSimilarity = 0;
    
    foreach (array_reverse($feedback) as $item) {
        $similarity = calculateSimilarity($question, $item['question']);
        
        // If exact match or very similar (similarity > 0.7)
        if ($similarity > 0.7 && $similarity > $highestSimilarity) {
            $relevantFeedback = $item;
            $highestSimilarity = $similarity;
        }
    }
    
    return $relevantFeedback;
}

# Function to load all feedback for context
function loadAllFeedback($feedbackFile) {
    if (!file_exists($feedbackFile)) {
        return [];
    }
    
    return json_decode(file_get_contents($feedbackFile), true) ?: [];
}

# Ask a question with custom instructions
$question = 'What roles are there in this system?';

# Check if there's existing feedback for this question
$existingFeedback = loadFeedback($question, $feedbackFile);
if ($existingFeedback) {
    echo "\n-- Previous correction for similar question:\n";
    echo "Question: " . $existingFeedback['question'] . "\n";
    echo "Original answer: " . $existingFeedback['original_answer'] . "\n";
    echo "Corrected answer: " . $existingFeedback['corrected_answer'] . "\n";
    
    # Add feedback to the prompt
    $feedbackContext = "\n\nPREVIOUS CORRECTIONS:\n";
    $feedbackContext .= "Question: " . $existingFeedback['question'] . "\n";
    $feedbackContext .= "Correct answer: " . $existingFeedback['corrected_answer'] . "\n";
    $feedbackContext .= "Please use this corrected information in your response if it's relevant to the current question.";
    
    $prompt = $customInstructions . $feedbackContext . "\n\nQuestion: " . $question;
} else {
    $prompt = $customInstructions . "\n\nQuestion: " . $question;
}

$answer = $qa->answerQuestion($prompt);
printf("-- Question: %s\n", $question);
printf("-- Answer:\n%s\n", $answer);

# Ask for feedback
echo "\n-- Feedback --\n";
echo "Is this answer correct? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) === 'n') {
    echo "Please enter the correct answer: ";
    $handle = fopen("php://stdin", "r");
    $correctedAnswer = trim(fgets($handle));
    fclose($handle);
    
    saveFeedback($question, $answer, $correctedAnswer, $feedbackFile);
}

// printf("\n-- Retrieved Documents:\n");
// foreach ($qa->getRetrievedDocuments() as $doc) {
//     printf("-- Document: %s\n", $doc->sourceName);
//     printf("-- Content (%d characters): %s\n\n", strlen($doc->content), $doc->content);
// }
