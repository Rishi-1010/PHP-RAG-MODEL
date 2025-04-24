<?php
/**
 * RAG architecture with Ollama and Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Elasticsearch\ElasticsearchVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

# Using tinyllama model which has lower memory requirements
$config = new OllamaConfig();
$config->model = 'tinyllama:latest';
$chat = new OllamaChat($config);

# Embedding
$embeddingGenerator = new OllamaEmbeddingGenerator($config);

# Elasticsearch
$es = (new ClientBuilder())::create()
    ->setHosts(['http://localhost:9200'])
    ->setApiKey('ZTlmd1haWUJxZXJKblNqc1BWVVY6UThqc2I3WHRTTGE2dTI0U1o0RnpqQQ==')
    ->build();

$elasticVectorStore = new ElasticsearchVectorStore($es, $indexName = 'ollama');

# RAG
$qa = new QuestionAnswering(
    $elasticVectorStore,
    $embeddingGenerator,
    $chat
);

$answer = $qa->answerQuestion('What are the key features and benefits of the ST ebook?');
printf("-- Answer:\n%s\n", $answer);

// foreach ($qa->getRetrievedDocuments() as $doc) {
//     printf("-- Document: %s\n", $doc->sourceName);
//     printf("-- Content (%d characters): %s\n", strlen($doc->content), $doc->content);
// }
