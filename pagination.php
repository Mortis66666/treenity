<?php

function renderPagination($currentPage, $totalPages, $baseUrl = '?page=')
{
    if ($totalPages <= 1) {
        return;
    }

    echo '<div class="pagination">';

    $currentPage = max(1, min($currentPage, $totalPages));
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        echo '<button onclick="window.location=\'' . $baseUrl . '1\'">1</button>';

        if ($start > 2) {
            echo '<span class="ellipsis">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $currentPage) ? ' class="active"' : '';

        echo '<button' . $active . ' onclick="window.location=\'' .
            $baseUrl . $i . '\'">' . $i . '</button>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            echo '<span class="ellipsis">...</span>';
        }

        echo '<button onclick="window.location=\'' . $baseUrl .
            $totalPages . '\'">' . $totalPages . '</button>';
    }

    echo '</div>';
}
