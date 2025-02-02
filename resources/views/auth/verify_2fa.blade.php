<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Verificación en dos pasos</h2>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('verify.2fa.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">Introduce el código</label>
                <input type="text" name="code" id="code" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Verificar</button>
        </form>

        <div class="mt-3">
            <button id="resendCodeBtn" class="btn btn-secondary" onclick="resendCode()">Reenviar código</button>
            <span id="countdownText" class="text-muted"></span>
        </div>
    </div>

    <script>
        let waitTime = {{ session('wait_time', 60) }};
        let countdown = 0;
        let interval;

        function startCountdown() {
            let resendBtn = document.getElementById("resendCodeBtn");
            let countdownText = document.getElementById("countdownText");

            resendBtn.disabled = true;
            countdown = waitTime;

            interval = setInterval(() => {
                countdown--;
                countdownText.textContent = ` (Espera ${countdown}s)`;

                if (countdown <= 0) {
                    clearInterval(interval);
                    resendBtn.disabled = false;
                    countdownText.textContent = "";
                }
            }, 1000);
        }

        function resendCode() {
            $.ajax({
                url: "{{ route('resend.2fa') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function(response) {
                    alert(response.message);
                    waitTime = response.new_wait_time;
                    startCountdown();
                },
                error: function(error) {
                    alert("Error al reenviar código.");
                }
            });
        }

        window.onload = function() {
            startCountdown();
        };
    </script>
</body>
</html>
