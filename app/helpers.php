<?php

if (!function_exists('renderStars')) {

    function renderStars($rating)
    {
        $full = floor($rating);
        $half = ($rating - $full) >= 0.25 && ($rating - $full) <= 0.75 ? 1 : 0;

        if (($rating - $full) > 0.75) { 
            $full++; 
            $half = 0; 
        }

        $empty = 5 - ($full + $half);

        $svgFull = '<svg width="20" height="20" fill="#FF9900" viewBox="0 0 24 24">
          <path d="M12 .587l3.668 7.568L24 9.748l-6 5.848L19.335 24 12 19.897 
          4.665 24 6 15.596l-6-5.848 8.332-1.593z"/></svg>';

        $svgHalf = '<svg width="20" height="20" viewBox="0 0 24 24">
          <defs>
            <linearGradient id="halfGrad">
              <stop offset="50%" stop-color="#FF9900"/>
              <stop offset="50%" stop-color="#ccc"/>
            </linearGradient>
          </defs>
          <path fill="url(#halfGrad)" d="M12 .587l3.668 7.568L24 9.748l-6 5.848
          L19.335 24 12 19.897V.587z"/>
          <path fill="#ccc" d="M12 .587L8.332 8.155 0 9.748l6 5.848L4.665 24 
          12 19.897z"/></svg>';

        $svgEmpty = '<svg width="20" height="20" fill="#ccc" viewBox="0 0 24 24">
          <path d="M12 .587l3.668 7.568L24 9.748l-6 5.848L19.335 24 
          12 19.897 4.665 24 6 15.596l-6-5.848 8.332-1.593z"/></svg>';

        return str_repeat($svgFull, $full)
             . str_repeat($svgHalf, $half)
             . str_repeat($svgEmpty, $empty);
    }
}
