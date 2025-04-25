# Installation Guide for OpenAI-based RAG System

This guide will help you set up the environment needed to run the OpenAI-based RAG (Retrieval Augmented Generation) system.

## Prerequisites

### 1. Install Docker and Docker Compose

#### Windows:
1. Download Docker Desktop for Windows from [Docker's official website](https://www.docker.com/products/docker-desktop)
2. Run the installer and follow the installation wizard
3. After installation, restart your computer
4. Verify installation by opening PowerShell and running:
   ```powershell
   docker --version
   docker-compose --version
   ```

### 2. Install PHP and Composer

#### Windows:
1. Download PHP from [PHP's official website](https://windows.php.net/download/)
2. Download Composer from [Composer's official website](https://getcomposer.org/download/)
3. Add PHP and Composer to your system PATH
4. Verify installation:
   ```powershell
   php -v
   composer --version
   ```
   

## Setup Elasticsearch with Docker

1. Create a `docker-compose.yml` file in your project root:
```yaml
version: '3'
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.1
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    ports:
      - "9200:9200"
    volumes:
      - elasticsearch-data:/usr/share/elasticsearch/data

volumes:
  elasticsearch-data:
```

2. Start Elasticsearch:
```bash
docker-compose up -d
```

3. Verify Elasticsearch is running:
```bash
curl http://localhost:9200
```

## Project Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd <repository-directory>
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create a `.env` file in the root directory:
```bash
OPENAI_API_KEY=your_openai_api_key
ELASTIC_URL=http://localhost:9200
ELASTIC_API_KEY=your_elasticsearch_api_key
```

4. Place your PDF documents in the `data/` directory

## Running the System

1. First, process and index your documents:
```bash
php src/rag/openai/embedding.php
```

2. Then you can ask questions:
```bash
php src/rag/openai/qa.php
```

## Troubleshooting

### Common Issues

1. **Elasticsearch Connection Issues**:
   - Ensure Elasticsearch is running: `docker ps`
   - Check Elasticsearch logs: `docker-compose logs elasticsearch`
   - Verify port 9200 is accessible: `curl http://localhost:9200`

2. **PHP Memory Issues**:
   - Increase PHP memory limit in `php.ini`:
     ```
     memory_limit = 512M
     ```

3. **OpenAI API Issues**:
   - Verify your API key is correct
   - Check your OpenAI account for usage limits
   - Ensure you have sufficient credits

### Getting Help

If you encounter any issues:
1. Check the error messages in the console
2. Verify all environment variables are set correctly
3. Ensure all prerequisites are installed and running
4. Check the Elasticsearch logs for any issues

## Security Notes

1. Never commit your API keys or sensitive information to version control
2. Keep your `.env` file secure and don't share it
3. Regularly update your dependencies for security patches
4. Use appropriate access controls for your Elasticsearch instance 




docker start elasticsearch
curl http://localhost:9200