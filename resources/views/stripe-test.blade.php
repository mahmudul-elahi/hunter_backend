<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Test — Get Payment Method ID</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f0f0f;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 36px;
            width: 100%;
            max-width: 460px;
        }

        h1 { font-size: 20px; font-weight: 600; margin-bottom: 6px; }
        p  { font-size: 13px; color: #888; margin-bottom: 28px; }

        label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #aaa;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #card-element {
            background: #111;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 20px;
        }

        #card-errors {
            color: #f87171;
            font-size: 13px;
            margin-bottom: 16px;
            min-height: 20px;
        }

        button {
            width: 100%;
            background: #00C853;
            color: #000;
            font-weight: 600;
            font-size: 15px;
            border: none;
            border-radius: 8px;
            padding: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        button:disabled { opacity: 0.5; cursor: not-allowed; }

        .result {
            display: none;
            margin-top: 24px;
            background: #111;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 16px;
        }

        .result label { margin-bottom: 10px; color: #00C853; }

        .result-value {
            font-family: monospace;
            font-size: 13px;
            color: #fff;
            word-break: break-all;
            background: #0a0a0a;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .copy-btn {
            background: #222;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            width: auto;
            border: 1px solid #333;
        }

        .test-cards {
            margin-top: 24px;
            border-top: 1px solid #2a2a2a;
            padding-top: 20px;
        }

        .test-cards p { margin-bottom: 12px; }

        .test-card-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px solid #1f1f1f;
        }

        .test-card-row span:first-child { color: #ccc; font-family: monospace; }
        .test-card-row span:last-child  { color: #888; }
    </style>
</head>
<body>
<div class="card">
    <h1>Stripe Test</h1>
    <p>Enter a test card to get a <code>payment_method_id</code> for your API requests.</p>

    <label>Card Details</label>
    <div id="card-element"></div>
    <div id="card-errors"></div>

    <button id="submit-btn">Generate Payment Method ID</button>

    <div class="result" id="result">
        <label>payment_method_id</label>
        <div class="result-value" id="pm-id"></div>
        <button class="copy-btn" id="copy-btn">Copy to clipboard</button>
    </div>

    <div class="test-cards">
        <p>Test card numbers</p>
        <div class="test-card-row">
            <span>4242 4242 4242 4242</span>
            <span>Visa — succeeds</span>
        </div>
        <div class="test-card-row">
            <span>5555 5555 5555 4444</span>
            <span>Mastercard — succeeds</span>
        </div>
        <div class="test-card-row">
            <span>4000 0000 0000 9995</span>
            <span>Insufficient funds</span>
        </div>
        <div class="test-card-row">
            <span>4000 0000 0000 0002</span>
            <span>Always declined</span>
        </div>
    </div>
</div>

<script>
    const stripe = Stripe('{{ config('cashier.key') }}');
    const elements = stripe.elements();

    const cardElement = elements.create('card', {
        style: {
            base: {
                color: '#fff',
                fontFamily: 'monospace',
                fontSize: '15px',
                '::placeholder': { color: '#555' },
            },
            invalid: { color: '#f87171' },
        },
    });

    cardElement.mount('#card-element');

    cardElement.on('change', ({ error }) => {
        document.getElementById('card-errors').textContent = error ? error.message : '';
    });

    document.getElementById('submit-btn').addEventListener('click', async () => {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Generating...';

        const { paymentMethod, error } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
        });

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            btn.disabled = false;
            btn.textContent = 'Generate Payment Method ID';
            return;
        }

        const result = document.getElementById('result');
        document.getElementById('pm-id').textContent = paymentMethod.id;
        result.style.display = 'block';
        btn.textContent = 'Generate Payment Method ID';
        btn.disabled = false;
    });

    document.getElementById('copy-btn').addEventListener('click', () => {
        const id = document.getElementById('pm-id').textContent;
        navigator.clipboard.writeText(id);
        document.getElementById('copy-btn').textContent = 'Copied!';
        setTimeout(() => document.getElementById('copy-btn').textContent = 'Copy to clipboard', 2000);
    });
</script>
</body>
</html>
