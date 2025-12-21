
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>COMPANY INFO</h3>
                    <ul>
                        <li><a href="#">About MTP Flex</a></li>
                        <li><a href="#">Social Responsibility</a></li>
                        <li><a href="#">Affiliate</a></li>
                        <li><a href="#">Fashion Blogger</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>HELP & SUPPORT</h3>
                    <ul>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Returns</a></li>
                        <li><a href="#">How to Order</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>CUSTOMER CARE</h3>
                    <ul>
                        <li><a href="contact_us.php">Contact Us</a></li>
                        <li><a href="#">Payment Method</a></li>
                        <li><a href="#">Bonus Point</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> MTP Flex Store. All rights reserved.</p>
                <div>
                    <a href="#">Privacy Policy</a> |
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
    <style>
        .footer { background-color: var(--primary-color); color: #9ca3af; padding: 4rem 0 2rem; margin-top: 4rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .footer-col h3 { color: white; font-size: 1rem; margin-bottom: 1.5rem; font-weight: 600; letter-spacing: 0.05em; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col ul li a { color: #9ca3af; text-decoration: none; transition: color 0.2s; font-size: 0.95rem; display: block; }
        .footer-col ul li a:hover { color: white; }
        
        .footer-bottom { border-top: 1px solid #374151; padding-top: 2rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center; }
        .footer-bottom a { color: #9ca3af; margin: 0 10px; text-decoration: none; font-size: 0.9rem; }
        .footer-bottom a:hover { color: white; }
        
        @media (min-width: 768px) {
            .footer-bottom { flex-direction: row; justify-content: space-between; }
        }

        main {
            min-height: calc(100vh - 450px); /* Adjust based on header/footer height */
        }
    </style>
</body>
</html>
