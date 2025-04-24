<?php
/**
 * Check vectors stored in Elasticsearch
 */
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;

# Elasticsearch connection
$es = (new ClientBuilder())::create()
    ->setHosts(['http://localhost:9200'])
    ->setApiKey('ZTlmd1haWUJxZXJKblNqc1BWVVY6UThqc2I3WHRTTGE2dTI0U1o0RnpqQQ==')
    ->build();

# Get all indices
echo "=== Elasticsearch Indices ===\n";
$indices = $es->cat()->indices(['v' => true]);
echo $indices->asString() . "\n";

# Get mapping for ollama index
echo "\n=== Ollama Index Mapping ===\n";
try {
    $mapping = $es->indices()->getMapping(['index' => 'ollama']);
    $mappingData = json_decode($mapping->asString(), true);
    echo json_encode($mappingData, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    printf("Error getting mapping: %s\n", $e->getMessage());
}

# Get a sample of documents
echo "\n=== Sample Documents from Ollama Index ===\n";
try {
    $params = [
        'index' => 'ollama',
        'body'  => [
            'size' => 2,
            '_source' => ['content', 'sourceName', 'sourceType'],
            'query' => [
                'match_all' => new \stdClass()
            ]
        ]
    ];
    
    $response = $es->search($params);
    $hits = json_decode($response->asString(), true)['hits']['hits'];
    foreach ($hits as $hit) {
        echo "Document Source: " . $hit['_source']['sourceName'] . "\n";
        echo "Source Type: " . $hit['_source']['sourceType'] . "\n";
        echo "Content:\n" . $hit['_source']['content'] . "\n\n";
    }
} catch (Exception $e) {
    printf("Error getting documents: %s\n", $e->getMessage());
} 