# LinkedIn Content Automation App

This web application helps users automate the process of posting to LinkedIn by combining AI-powered content generation with human-like rewriting. Built to streamline the content creation process for job seekers, personal brands, and business professionals.

## 🌐 Live App

👉 [linkedin.iwaola.me](https://linkedin.iwaola.me)

---

## ✨ Features

- ✅ **Topic Importing** – Users can upload or enter multiple content topics at once.
- ✍️ **AI-Powered Post Generation** – The app generates relevant LinkedIn posts for approved topics.
- 🤖 **Humanized Writing** – Generated posts are rewritten using OpenAI to feel more natural and authentic.
- 🕵️‍♂️ **Post Approval Workflow** – Users can approve or edit posts before they’re published.
- 📅 **LinkedIn Integration** – Once approved, posts are automatically published to the user’s LinkedIn account.
- 🔐 **Authentication-Based Access** – Authenticated users are redirected to their dashboard; others go to the login page.
- 📊 **Dashboard Overview** – View and manage your topic pipeline, approved posts, and publishing schedule.

---

## 🔁 How It Works

1. **User logs in** via the web interface.
2. **Topics are imported** manually or in bulk.
3. User **approves the topics** they want to turn into posts.
4. The app **generates AI-written posts** for each approved topic.
5. These posts are then **humanized using OpenAI**.
6. User **reviews and approves** each final post.
7. Approved posts are **automatically published to LinkedIn**.

---

## 🔧 Tech Stack

- **Laravel** + **Livewire** + **FilamentPHP**
- **Tailwind CSS**
- **OpenAI API**
- **LinkedIn API**
- **MySQL**
- **Blade templates**

---

## ⚙️ Environment Setup

Create a `.env` file using the example and configure these values:

```env
OPENAI_API_KEY=your_openai_api_key

LINKEDIN_CLIENT_ID=""
LINKEDIN_CLIENT_SECRET=""
LINKEDIN_REDIRECT_URI="${APP_URL}/linkedin/callback"
```

---

## 📦 Installation

```bash
git clone https://github.com/yourusername/linkedin-automation-app.git
cd linkedin-automation-app
composer install
cp .env.example .env
php artisan key:generate
# Add your environment variables (see above)
php artisan migrate
php artisan serve
```

---

## 📣 Contact

Want to use this app or integrate it into your workflow?  
📬 [Contact Me](https://iwaola.me#contact)

---

## 🧑‍💻 Author

Made with ❤️ by [Fawaz Iwalewa](https://iwaola.me)
