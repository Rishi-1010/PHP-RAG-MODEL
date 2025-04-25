<?php
/**
 * Embedding with Ollama (TinyLlama)
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\OllamaConfig;

# Using tinyllama model which has lower memory requirements
$config = new OllamaConfig();
$config->model = 'tinyllama:latest';
$chat = new OllamaChat(config: $config);

# Read PDF file
printf ("- Reading the PDF files\n");
$reader = new FileDataReader(dirname(dirname(dirname(__DIR__))) . '/data/questions_and_answers.pdf');
$documents = $reader->getDocuments();
printf("Number of PDF files: %d\n", count($documents));

# Document split
printf("- Document split\n");
$splitDocuments = DocumentSplitter::splitDocuments($documents, 1000);
printf("Number of splitted documents (chunk): %d\n", count($splitDocuments));

# Embedding
printf("- Generating embeddings\n");
$embeddingGenerator = new OllamaEmbeddingGenerator($config);
$embeddedDocuments = $embeddingGenerator->embedDocuments($splitDocuments);

# Save embeddings to JSON file
printf("- Saving embeddings to vectordb.json\n");
$embeddingsData = [];
foreach ($embeddedDocuments as $doc) {
    $embeddingsData[] = [
        'content' => $doc->content,
        'embedding' => $doc->embedding,
        'metadata' => $doc->metadata
    ];
}

$jsonData = json_encode($embeddingsData, JSON_PRETTY_PRINT);
file_put_contents(dirname(dirname(dirname(__DIR__))) . '/data/vectordb.json', $jsonData);
printf("Saved %d embeddings to vectordb.json\n", count($embeddingsData));

# Optional: Index to Elasticsearch
$useElasticsearch = false; // Set to true if you want to use Elasticsearch
if ($useElasticsearch) {
    printf("- Indexing to Elasticsearch\n");
    $es = (new ClientBuilder())::create()
        ->setHosts(['http://localhost:9200'])
        ->setApiKey('ZTlmd1haWUJxZXJKblNqc1BWVVY6UThqc2I3WHRTTGE2dTI0U1o0RnpqQQ==')
        ->build();

    $elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');
    $elasticVectorStore->addDocuments($embeddedDocuments);
    printf("Added %d documents to Elasticsearch\n", count($embeddedDocuments));
}