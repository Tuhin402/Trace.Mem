# API Overview

Welcome to the TraceMem API. The API is organised around REST and uses standard HTTP methods, authentication, and response codes.

**Base URL:** `https://api.tracemem.one/v1`

## Core Concepts

- **Memory Ingestion:** Store semantic facts, preferences, rules, and skills for a specific user using `/remember`.
- **Memory Retrieval:** Fetch raw memories for a user using `/recall`.
- **Context Assembly:** Automatically rank and format memories into a prompt-ready string using `/context/assemble`.
- **Chat:** A one-call convenience endpoint (`/chat`) that automatically classifies, remembers, assembles context, and generates an AI reply.

## Content Types

All API requests require the `Content-Type: application/json` header. Responses are consistently returned in JSON format.
