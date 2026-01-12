<?php
// Mouse Cursor Follow Effect - Outlined Circle
?>

<!-- Mouse Circle -->
<div class="mouse-circle"></div>

<style>
.mouse-circle {
    position: fixed;
    top: 0;
    left: 0;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.7);
    /* line color */
    background: transparent;
    /* hollow center */
    pointer-events: none;
    transform: translate(-50%, -50%);
    z-index: 9999;
}

/* Hide on mobile */
@media (max-width: 768px) {
    .mouse-circle {
        display: none;
    }
}
</style>

<script>
(() => {
    try {
        const circle = document.querySelector('.mouse-circle');
        if (!circle) {
            console.warn('Mouse circle element not found');
            return;
        }

        let mouseX = 0,
            mouseY = 0;
        let circleX = 0,
            circleY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });

        function animate() {
            circleX += (mouseX - circleX) * 0.15;
            circleY += (mouseY - circleY) * 0.15;

            circle.style.left = circleX + 'px';
            circle.style.top = circleY + 'px';

            requestAnimationFrame(animate);
        }

        animate();
    } catch (error) {
        console.error('Mouse cursor animation error:', error);
    }
})();
</script>
