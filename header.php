<?php
declare(strict_types=1);
?>
<header class="tui-status-top">
    <div class="tui-status-left">
        <a href="https://marechan.eu" class="tui-link">:/HOME/</a>
		<select id="theme-selector" class="tui-dropdown" aria-label="Select terminal color theme">
			<option class="tui-board-id">5chan/b</option>
		</select>
    </div>
    <div>
		<span>[<?= $forum . " : " . $page_name ?>]</span>
	</div>
    <div class="tui-status-right">
        <label for="theme-selector" class="tui-mode-label">MODE:</label>
        <select id="theme-selector" class="tui-dropdown" aria-label="Select terminal color theme">
            <option value="term-mono">TERM-MONO</option>
            <option value="term-green">TERM-GREEN</option>
            <option value="term-amber">TERM-AMBER</option>
            <option value="term-matrix">TERM-MATRIX</option>
        </select>
    </div>
</header>
<script>
    (function() {
        const savedTheme = localStorage.getItem('5chan_theme') || 'term-mono';
        document.documentElement.setAttribute('data-theme', savedTheme);
        const sel = document.getElementById('theme-selector');
        if (sel) {
            sel.value = savedTheme;
            sel.addEventListener('change', function() {
                document.documentElement.setAttribute('data-theme', this.value);
                localStorage.setItem('5chan_theme', this.value);
            });
        }
    })();
</script>
