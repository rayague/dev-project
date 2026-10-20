<?php
/**
 * ============================================================
 *  VUE : layouts/footer.php  (FIN DE TOUTES LES PAGES)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Ferme les balises ouvertes dans layouts/header.php :
 *   - Utilisateur CONNECTÉ : ferme </main>, la colonne principale
 *     </div>, puis le conteneur flex </div>.
 *   - Visiteur NON connecté : ferme uniquement </main>.
 *
 *  Contient aussi le PRELOADER : un écran de chargement premium,
 *  purement CSS + quelques lignes JavaScript (aucune ressource
 *  externe). Il se retire doucement dès que la page est prête.
 * ============================================================
 */
?>
<?php if (Auth::check()): ?>
                    <!-- Fin de la zone de contenu principal -->
                    </main>
                </div>
                <!-- Fin de la colonne droite (barre du haut + contenu) -->
            </div>
            <!-- Fin du conteneur flex (sidebar + contenu) -->
<?php else: ?>
        <!-- Fin de la zone de contenu (page centrée) -->
        </main>
<?php endif; ?>

    <!-- ========================================================
         PRELOADER (écran de chargement « Aurore »)
         Styles : public/assets/css/app.css (section 13).
         ======================================================== -->
    <div id="preloader" class="preloader" aria-hidden="true">
        <div class="preloader-core">
            <div class="relative flex h-16 w-16 items-center justify-center">
                <!-- Anneau dégradé qui tourne -->
                <div class="preloader-ring"></div>
                <!-- Marque centrale -->
                <div class="preloader-mark">G</div>
            </div>
            <p class="preloader-name"><?= e(APP_NAME) ?></p>
            <div class="preloader-bar"><span></span></div>
        </div>
    </div>

    <!-- Petit script de fermeture du preloader (aucune dépendance) -->
    <script>
        (function () {
            var preloader = document.getElementById('preloader');
            function cacher() {
                if (!preloader) return;
                if (preloader.classList.contains('is-hidden')) return;
                // Fondu puis retrait du DOM (l'animation est en CSS).
                preloader.classList.add('is-hidden');
                window.setTimeout(function () {
                    if (preloader.parentNode) preloader.parentNode.removeChild(preloader);
                }, 600);
            }
            if (document.readyState === 'complete') {
                cacher();
            } else {
                window.addEventListener('load', cacher);
            }
            // Sécurité : ne jamais rester bloqué à l'écran.
            window.setTimeout(cacher, 1500);
        })();
    </script>

    </body>
</html>