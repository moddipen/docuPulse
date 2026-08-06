<!DOCTYPE html>
<html>
<head>
    <title>DocuPulse AI — Contract Analysis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- minimal styling — enough to look intentional, not a project -->
</head>
<body>
    <h1>DocuPulse AI</h1>
    <p>Ask a question about the contract.</p>

    <input type="text" id="question" placeholder="What is the confidentiality survival period?" />
    <button id="askBtn">Ask</button>

    <div id="answer"></div>

    <script>
        const input  = document.getElementById('question');
        const button = document.getElementById('askBtn');
        const answer = document.getElementById('answer');

        async function ask() {
            const question = input.value.trim();
            if (!question) {
                answer.textContent = 'Please type a question first.';
                return;
            }

            // Loading state: disable the button and show progress so the
            // few-second wait doesn't look like a frozen page.
            button.disabled = true;
            answer.textContent = 'Thinking…';

            try {
                const response = await fetch('/api/ask', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ question, tenant_id: 1 }),
                });

                const data = await response.json();

                // A non-2xx status (422 validation, 502 generation failure, …)
                // still returns JSON — surface its message instead of the answer.
                if (!response.ok) {
                    answer.textContent = data.message || 'Something went wrong. Please try again.';
                    return;
                }

                answer.textContent = data.answer;
            } catch (error) {
                // Network error / server unreachable — never leave the user staring at nothing.
                answer.textContent = 'Could not reach the server. Check your connection and try again.';
            } finally {
                button.disabled = false;
            }
        }

        button.addEventListener('click', ask);
        // Enter key in the input triggers the same flow.
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') ask();
        });
    </script>
</body>
</html>