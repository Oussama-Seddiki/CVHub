# CVHub - Professional Document Platform

CVHub is a comprehensive web platform for professional document creation and management, built with Laravel and Vue.js.

## Features

- **Resume Builder:** Create professional resumes using AI-powered tools with the Resumaker AI API
- **Document Library:** Access educational resources and documents using Scribd API integration
- **File Processing:** Convert, merge, split, and protect PDF files with ilovepdf API
- **Interactive UI:** Enhanced user experience with cursor effects and animations

## Tech Stack

- **Backend:** Laravel
- **Frontend:** Vue.js, Pinia, Vue Router
- **Styling:** Tailwind CSS
- **UI Enhancement:** cursor-effects library
- **External APIs:**
  - Resumaker AI API for resume generation
  - Scribd API for document embedding
  - ilovepdf API for file processing

## Setup Instructions

### Prerequisites

- PHP 8.2+
- Composer
- Node.js and npm
- MySQL or other database supported by Laravel

### Installation

1. Clone the repository:
   ```
   git clone https://github.com/your-username/cvhub.git
   cd cvhub
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install JavaScript dependencies:
   ```
   npm install
   ```

4. Create your environment file:
   ```
   cp .env.example .env
   ```

5. Generate application key:
   ```
   php artisan key:generate
   ```

6. Configure your database in the `.env` file.

7. Run migrations:
   ```
   php artisan migrate
   ```

8. Start the development server:
   ```
   npm run dev
   php artisan serve
   ```

### API Configuration

To fully utilize the platform, you'll need to obtain API keys for:

1. **Resumaker AI API:** Sign up at [https://resumaker.ai](https://resumaker.ai) and get your API key
2. **Scribd API:** Register as a developer at [https://www.scribd.com/developers](https://www.scribd.com/developers)
3. **ilovepdf API:** Get your API credentials from [https://developer.ilovepdf.com](https://developer.ilovepdf.com)

Add these credentials to your `.env` file:

```
RESUMAKER_API_KEY=your-api-key
SCRIBD_API_KEY=your-api-key
SCRIBD_API_SECRET=your-api-secret
ILOVEPDF_PUBLIC_KEY=your-public-key
ILOVEPDF_SECRET_KEY=your-secret-key
```

## Usage

Once set up, you can access the application at `http://localhost:8000`. 

The platform offers three main services:
1. **Resume Builder:** Create professional resumes by filling out your details and selecting templates
2. **Document Library:** Browse, search, and view documents from the digital library
3. **File Processing:** Process PDF files with various tools for conversion, merging, splitting, and protection

## License

[MIT License](LICENSE)

## Credits

- cursor-effects library by [tholman](https://github.com/tholman/cursor-effects)
- Resumaker AI API for intelligent resume generation
- Scribd API for document embedding
- ilovepdf API for PDF processing capabilities
