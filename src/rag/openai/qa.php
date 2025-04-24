<?php
/**
 * RAG architecture with OpenAI and Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Debug information
echo "Current directory: " . __DIR__ . "\n";
$rootDir = dirname(dirname(dirname(__DIR__)));
echo "Root directory: " . $rootDir . "\n";

// Load environment variables from config directory
$dotenv = Dotenv\Dotenv::createImmutable(dirname(dirname(dirname(__DIR__))) . '/config');
$dotenv->load();

// Debug: Print all environment variables
echo "\nCurrent environment variables:\n";
echo "OPENAI_API_KEY: " . (isset($_ENV['OPENAI_API_KEY']) ? 'Set' : 'Not Set') . "\n";
echo "ELASTIC_URL: " . (isset($_ENV['ELASTIC_URL']) ? 'Set' : 'Not Set') . "\n";
echo "ELASTIC_API_KEY: " . (isset($_ENV['ELASTIC_API_KEY']) ? 'Set' : 'Not Set') . "\n";

// Also check $_SERVER
echo "\nServer environment variables:\n";
echo "OPENAI_API_KEY: " . (isset($_SERVER['OPENAI_API_KEY']) ? 'Set' : 'Not Set') . "\n";
echo "ELASTIC_URL: " . (isset($_SERVER['ELASTIC_URL']) ? 'Set' : 'Not Set') . "\n";
echo "ELASTIC_API_KEY: " . (isset($_SERVER['ELASTIC_API_KEY']) ? 'Set' : 'Not Set') . "\n";

// Verify environment variables
if (!isset($_ENV['OPENAI_API_KEY']) && !isset($_SERVER['OPENAI_API_KEY'])) {
    die("Error: OPENAI_API_KEY environment variable is not set in config/.env\n");
}
if (!isset($_ENV['ELASTIC_URL']) && !isset($_SERVER['ELASTIC_URL'])) {
    die("Error: ELASTIC_URL environment variable is not set in config/.env\n");
}
if (!isset($_ENV['ELASTIC_API_KEY']) && !isset($_SERVER['ELASTIC_API_KEY'])) {
    die("Error: ELASTIC_API_KEY environment variable is not set in config/.env\n");
}

// Set the variables for use in the rest of the code
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'];
$elasticUrl = $_ENV['ELASTIC_URL'] ?? $_SERVER['ELASTIC_URL'];
$elasticApiKey = $_ENV['ELASTIC_API_KEY'] ?? $_SERVER['ELASTIC_API_KEY'];

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\OpenAIConfig;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

$config = new OpenAIConfig();
$config->apiKey = $openaiApiKey;
$config->model = 'gpt-3.5-turbo';
$chat = new OpenAIChat($config);

# Embedding
echo "\nInitializing embedding generator...\n";
$embeddingConfig = new OpenAIConfig();
$embeddingConfig->apiKey = $openaiApiKey;
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($embeddingConfig);
echo "Embedding generator initialized\n";

# Elasticsearch
echo "\nConnecting to Elasticsearch...\n";
$es = (new ClientBuilder())::create()
    ->setHosts([$elasticUrl])
    ->setApiKey($elasticApiKey)
    ->build();
echo "Connected to Elasticsearch\n";

$elasticVectorStore = new ElasticsearchVectorStore($es);

# RAG
echo "\nInitializing Question Answering system...\n";
$qa = new QuestionAnswering(
    $elasticVectorStore,
    $embeddingGenerator,
    $chat
);
echo "Question Answering system initialized\n";

# Ask a question
if ($argc < 2) {
    die("Please provide a question as a command-line argument.\nUsage: php qa.php \"Your question here\"\n");
}

$question = $argv[1];
printf("\nQuestion: %s\n", $question);

echo "\nGenerating embedding for the question...\n";
$answer = $qa->answerQuestion($question);
printf("-- Answer:\n%s\n", $answer);

# Show retrieved documents
printf("\n-- Retrieved Documents:\n");
foreach ($qa->getRetrievedDocuments() as $doc) {
    printf("-- Document: %s\n", $doc->sourceName);
    printf("-- Chunk %d: %d characters\n", $doc->chunkNumber, strlen($doc->content));
    printf("-- Embedding vector length: %d\n", count($doc->embedding));
    printf("-- First few embedding values: %s\n", implode(', ', array_slice($doc->embedding, 0, 5)));
}
