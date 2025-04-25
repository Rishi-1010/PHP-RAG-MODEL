<?php
/**
 * Embedding with OpenAI
 */
ini_set('memory_limit', '2G'); // Increase memory limit to 2GB
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Debug information
echo "Current directory: " . __DIR__ . "\n";
$rootDir = dirname(dirname(dirname(__DIR__)));
echo "Root directory: " . $rootDir . "\n";

// Set PDF path
$pdfPath = $rootDir . '/data/HISTORY-ENGLISH.pdf';
echo "PDF path: " . $pdfPath . "\n";
echo "PDF file exists: " . (file_exists($pdfPath) ? "Yes" : "No") . "\n\n";

// Try to load .env file directly
$envFile = $rootDir . '/config/.env';
echo "Looking for .env file at: " . $envFile . "\n";
echo "File exists: " . (file_exists($envFile) ? "Yes" : "No") . "\n";

if (file_exists($envFile)) {
    echo "\n.env file contents:\n";
    echo file_get_contents($envFile) . "\n";
} else {
    die("Error: .env file not found at " . $envFile . "\n");
}

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir . '/config');
    $dotenv->load();
    echo "\nEnvironment variables loaded successfully\n";
    
    // Debug: Print all environment variables
    // echo "\nCurrent environment variables:\n";
    // echo "OPENAI_API_KEY: " . (isset($_ENV['OPENAI_API_KEY']) ? 'Set' : 'Not Set') . "\n";
    // echo "ELASTIC_URL: " . (isset($_ENV['ELASTIC_URL']) ? 'Set' : 'Not Set') . "\n";
    // echo "ELASTIC_API_KEY: " . (isset($_ENV['ELASTIC_API_KEY']) ? 'Set' : 'Not Set') . "\n";

    // Also check $_SERVER
    // echo "\nServer environment variables:\n";
    // echo "OPENAI_API_KEY: " . (isset($_SERVER['OPENAI_API_KEY']) ? 'Set' : 'Not Set') . "\n";
    // echo "ELASTIC_URL: " . (isset($_SERVER['ELASTIC_URL']) ? 'Set' : 'Not Set') . "\n";
    // echo "ELASTIC_API_KEY: " . (isset($_SERVER['ELASTIC_API_KEY']) ? 'Set' : 'Not Set') . "\n";
} catch (\Exception $e) {
    die("Error loading .env file: " . $e->getMessage() . "\n");
}

// Verify environment variables
// if (!isset($_ENV['OPENAI_API_KEY']) && !isset($_SERVER['OPENAI_API_KEY'])) {
//     die("Error: OPENAI_API_KEY environment variable is not set in config/.env\n");
// }
// if (!isset($_ENV['ELASTIC_URL']) && !isset($_SERVER['ELASTIC_URL'])) {
//     die("Error: ELASTIC_URL environment variable is not set in config/.env\n");
// }
// if (!isset($_ENV['ELASTIC_API_KEY']) && !isset($_SERVER['ELASTIC_API_KEY'])) {
//     die("Error: ELASTIC_API_KEY environment variable is not set in config/.env\n");
// }

// Set the variables for use in the rest of the code
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'];
$elasticUrl = $_ENV['ELASTIC_URL'] ?? $_SERVER['ELASTIC_URL'];
$elasticApiKey = $_ENV['ELASTIC_API_KEY'] ?? $_SERVER['ELASTIC_API_KEY'];

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\OpenAIConfig;

# Read PDF file
printf("- Reading the PDF file\n");
$reader = new FileDataReader($pdfPath);
$documents = $reader->getDocuments();
printf("Number of documents: %d\n", count($documents));

if (count($documents) > 0) {
    printf("First document content length: %d\n", strlen($documents[0]->content));
    printf("First document source: %s\n", $documents[0]->sourceName);
}

# Document split
printf("- Document split\n");
$splitDocuments = DocumentSplitter::splitDocuments($documents, 1000);
printf("Number of splitted documents (chunk): %d\n", count($splitDocuments));

# Embedding
printf("- Generating embeddings\n");
$config = new OpenAIConfig();
$config->apiKey = $openaiApiKey;
$embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);
$embeddedDocuments = $embeddingGenerator->embedDocuments($splitDocuments);

# Save all embeddings to JSON file
printf("- Saving all embeddings to embeddings.json\n");
$embeddingsData = [];
foreach ($embeddedDocuments as $index => $doc) {
    $embeddingsData[] = [
        'chunk_number' => $index + 1,
        'content' => $doc->content,
        'source' => $doc->sourceName,
        'embedding_vector' => $doc->embedding,
        'vector_length' => count($doc->embedding)
    ];
}

$jsonFile = $rootDir . '/data/embeddings.json';
file_put_contents($jsonFile, json_encode($embeddingsData, JSON_PRETTY_PRINT));
printf("All embeddings saved to: %s\n", $jsonFile);

# Elasticsearch
printf("- Index all the embeddings to Elasticsearch\n");
$es = (new ClientBuilder())::create()
    ->setHosts([$elasticUrl])
    ->setApiKey($elasticApiKey)
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es);
$elasticVectorStore->addDocuments($embeddedDocuments);

printf("Added %d documents in Elasticsearch with embedding included\n", count($embeddedDocuments));