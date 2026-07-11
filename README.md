# MentalCare AI Chatbot (mental_care-AI)

A responsive, mobile-first chatbot interface designed for mental health support. Built with a premium, calming design system (sage greens and soft blues), strict adherence to Human-Computer Interaction (HCI) guidelines, and secure server-side cURL relays to the DeepSeek API using PHP.

## File Structure

```text
/mental_care-AI/
├── index.php                  # Main frontend interface & layout
├── api_handler.php            # Secure server-side cURL endpoint
├── .env                       # API Configuration file (ignored in production VCS)
├── .env.example               # Template environment configuration file
└── assets/
    ├── css/
    │   └── style.css          # Calming, high-contrast, mobile-first design styles
    └── js/
        └── script.js          # Main JS controller (AJAX, State, local storage, animation states)
```

## Setup & Deployment Instructions

### Option 1: Local XAMPP Environment (Recommended)

1. **Move Project to htdocs**:
   Copy the `mental_care-AI` folder into your XAMPP installation directory under the `htdocs` folder:
   * **Windows Path**: `C:\xampp\htdocs\mental_care-AI\`

2. **Configure API Key**:
   * Open the `.env` file inside the project directory.
   * Replace `your_deepseek_api_key_here` with your actual DeepSeek API key:
     ```ini
     DEEPSEEK_API_KEY=sk-your-actual-api-key-here
     ```
   * Save the file.

3. **Start Apache**:
   * Open the **XAMPP Control Panel**.
   * Click **Start** next to the **Apache** service.

4. **Access the Chatbot**:
   * Open your web browser and navigate to:
     `http://localhost/mental_care-AI/`

---

### Option 2: Run Using PHP Built-In Web Server (Quick Testing)

If you have PHP installed globally on your machine, you can run the application directly from the command line without XAMPP:

1. Open a terminal/command prompt in the `mental_care-AI` directory.
2. Run the following command:
   ```bash
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   `http://localhost:8000`

---

## Implemented HCI Principles & Features

1. **Visibility of System Status**: A custom pulsing/typing loading animation (`.typing-indicator`) appears while waiting for a response from the DeepSeek API, confirming that the request is processing.
2. **User Control and Freedom**: A dedicated "Clear Conversation" button resets the conversation state, clear local history, and restores the original welcome message.
3. **Error Prevention & Graceful Recovery**: If the network drops or the API key is missing/invalid, the app intercepts raw errors and prints a soothing, empathetic notification instead of code stacks.
4. **Consistency and Design System**: Calming color system using Sage Green and Soft Blue, with high text-contrast styles exceeding WCAG AA recommendations. Clearly defined styling states differentiate between assistant and user inputs.
5. **Permanent Safety Disclaimer**: A footer banner explaining the AI nature of the tool is always visible, displaying key helpline coordinates (`988`, `741741`) with dynamic highlight frames if emergency situations are referenced.
6. **Mood Check-in Drawer**: Click the smiley emoji button to toggle a mood selection box to quickly input emotional check-in cues.
