# PHP RAG System with OpenAI and Elasticsearch

A powerful Retrieval Augmented Generation (RAG) system built with PHP, OpenAI, and Elasticsearch. This system allows you to process PDF documents and answer questions about their content using advanced AI capabilities.

## Features

- Process and index PDF documents using OpenAI embeddings
- Semantic search capabilities using Elasticsearch
- Question answering powered by OpenAI's GPT models
- Docker-based Elasticsearch setup for easy deployment
- Support for large documents through chunking
- Document source tracking and citation

## Quick Start

1. Clone the repository:
```bash
git clone https://github.com/yourusername/php-rag-system.git
cd php-rag-system
```

2. Follow the installation guide in [INSTALL.md](INSTALL.md)

3. Run the system:
```bash
# Process and index your documents
php src/rag/openai/embedding.php

# Ask questions about your documents
php src/rag/openai/qa.php
```

## Project Structure

```
php-rag-system/
├── data/                  # Place your PDF documents here
├── src/
│   └── rag/
│       └── openai/       # OpenAI-based RAG implementation
│           ├── embedding.php
│           └── qa.php
├── vendor/               # Composer dependencies
├── .env                  # Configuration file (create this)
├── docker-compose.yml    # Elasticsearch configuration
├── composer.json         # PHP dependencies
├── README.md            # This file
└── INSTALL.md           # Installation guide
```

## Requirements

- PHP 8.0 or higher
- Composer
- Docker and Docker Compose
- OpenAI API key
- Elasticsearch (provided via Docker)

## Configuration

Create a `.env` file in the root directory with the following variables:

```bash
OPENAI_API_KEY=your_openai_api_key
ELASTIC_URL=http://localhost:9200
ELASTIC_API_KEY=your_elasticsearch_api_key
```

## Usage

1. Place your PDF documents in the `data/` directory
2. Run the embedding script to process and index your documents:
```bash
php src/rag/openai/embedding.php
```

3. Ask questions about your documents:
```bash
php src/rag/openai/qa.php
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- [OpenAI](https://openai.com/) for their powerful language models
- [Elasticsearch](https://www.elastic.co/) for their search capabilities
- [LLPhant](https://github.com/theodo-group/LLPhant) for their PHP LLM integration library

## Support

If you encounter any issues or have questions, please:
1. Check the [Troubleshooting](INSTALL.md#troubleshooting) section
2. Open an issue in the GitHub repository
3. Contact the maintainers

## Security

Please report any security issues to the maintainers directly. Do not disclose security issues publicly until they have been handled by the maintainers.


