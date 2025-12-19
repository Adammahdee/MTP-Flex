
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> MTP Flex Store. All rights reserved.</p>
        <div>
            <a href="#">Privacy Policy</a> |
            <a href="#">Terms of Service</a>
        </div>
    </footer>
    <style>
        .footer { background-color: var(--primary-color); color: var(--text-secondary); padding: 3rem 0; text-align: center; margin-top: 2rem; }
        .footer a { color: white; margin: 0 10px; text-decoration: none; }
        main {
            min-height: calc(100vh - 250px); /* Adjust based on header/footer height */
        }
    </style>
</body>
</html>
