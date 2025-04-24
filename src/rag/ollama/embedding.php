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
$reader = new FileDataReader(dirname(dirname(dirname(__DIR__))) . '/data/new ST ebook.pdf');
$documents = $reader->getDocuments();
printf("Number of PDF files: %d\n", count($documents));

# Document split
printf("- Document split\n");
$splitDocuments = DocumentSplitter::splitDocuments($documents, 1000);
printf("Number of splitted documents (chunk): %d\n", count($splitDocuments));

# Embedding
printf("- Embedding\n");
$embeddingGenerator = new OllamaEmbeddingGenerator($config);
$embeddedDocuments = $embeddingGenerator->embedDocuments($splitDocuments);

# Elasticsearch
printf("- Index all the embeddings to Elasticsearch\n");
$es = (new ClientBuilder())::create()
    ->setHosts(['http://localhost:9200'])
    ->setApiKey('ZTlmd1haWUJxZXJKblNqc1BWVVY6UThqc2I3WHRTTGE2dTI0U1o0RnpqQQ==')
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');
$elasticVectorStore->addDocuments($embeddedDocuments);

printf("Added %d documents in Elasticsearch with embedding included\n", count($embeddedDocuments));