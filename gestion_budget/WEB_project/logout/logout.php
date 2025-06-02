<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Votre Application</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Votre CSS existant */
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #000000, #121212, #1a1a1a);
            color: #ffffff;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 4rem;
            background-color: transparent;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(90deg, #FFD700, #D4AF37, #C5A100);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Logout Container */
        .logout-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            padding: 2rem;
            text-align: center;
        }

        .logout-box {
            background: rgba(26, 26, 26, 0.8);
            padding: 3rem;
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 100%;
        }

        .logout-box h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, #FFD700, #D4AF37, #C5A100);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .logout-box p {
            color: #cccccc;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .logout-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .logout-btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-logout {
            background: linear-gradient(45deg, #D4AF37, #FFD700, #C5A100);
            color: #000000;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .cancel-logout {
            background: none;
            border: 2px solid #D4AF37;
            color: #FFD700;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">Expencio</div>
    </nav>

    <!-- Logout Content -->
    <div class="logout-container">
        <div class="logout-box">
            <h1>logout</h1>
            <p>Do you really want to log out ??</p>
            
            <div class="logout-buttons">
                <button class="logout-btn confirm-logout">Yes, i want to log out</button>
                <button class="logout-btn cancel-logout">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        // Script pour gérer la déconnexion
        document.querySelector('.confirm-logout').addEventListener('click', function() {
            // Ici vous ajouteriez la logique de déconnexion réelle
            alert('Thank you for using our app . You are logged out succesfully');
            // Redirection vers la page de connexion
            window.location.href = '../../WEB_project/first_page/index.html';
        });

        document.querySelector('.cancel-logout').addEventListener('click', function() {
            // Retour à la page précédente ou à l'accueil
            window.history.back();
        });
    </script>
</body>
</html>