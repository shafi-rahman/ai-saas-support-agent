# 🤖 AI SaaS Support Agent  
### 🏢 Multi-Tenant | 🧠 RAG | 💻 Self-Hosted LLM (Ollama) | ⚡ Laravel 12

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red?logo=laravel" />
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?logo=php" />
  <img src="https://img.shields.io/badge/LLM-Ollama-black" />
  <img src="https://img.shields.io/badge/VectorDB-Qdrant-purple" />
  <img src="https://img.shields.io/badge/Architecture-RAG-orange" />
  <img src="https://img.shields.io/badge/License-MIT-green" />
</p>

---

## 🚀 Overview

A production-ready AI support platform that allows companies to:

- Upload documents 📄  
- Train their own AI assistant 🤖  
- Embed it into any website 🌐  

👉 Powered by local LLMs (Ollama) — zero API cost

---

## 🎬 Demo Flow

1. Register a company (tenant)  
2. Upload documents  
3. Convert → embeddings (Qdrant)  
4. Ask questions  
5. AI retrieves context (RAG)  
6. Ollama generates response  
7. Streaming answer to user  

---

## 🖥️ Screenshots

### Authentication
![Register](./register.png)
![Login](./login.png)

### Dashboard
![Dashboard](./ai-dashboard.png)

### AI Assistant
![AI Assistant](./ai-assistent.png)

### Widget
![Widget](./code-pest.png)

---

## 🧠 Features

- Multi-tenant architecture  
- Document ingestion (PDF, DOCX, CSV, TXT, URL)  
- RAG-based AI  
- Local LLM (Ollama)  
- Streaming responses  
- REST API  
- Embeddable widget  
- Queue processing  

---

## 🏗️ Architecture

User → Laravel → RAG → Qdrant → Ollama → Response

---

## ⚙️ Tech Stack

- Laravel 12  
- PHP 8.2+  
- MySQL  
- Qdrant  
- Ollama  
- Tailwind CSS  

---

## 🚀 Quick Start

git clone https://github.com/shafi-rahman/ai-saas-support-agent.git
cd ai-saas-support-agent/laravel-app

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

php artisan serve
php artisan queue:work

---

## 👨‍💻 Author

Shafi Rahman  
Senior PHP / Laravel Developer  

---

## 📄 License

MIT
