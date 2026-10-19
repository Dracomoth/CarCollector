/**
 * Dashboard Interactive Controller
 * 
 * Manages hover telemetry for the Brand Distribution Donut Chart,
 * synchronizing SVG slices, central readout metrics, and legend rows.
 */

document.addEventListener('DOMContentLoaded', function() {
    var donutSvg = document.getElementById('dashboard-donut-svg');
    var centerMetric = document.getElementById('donut-center-metric');
    var centerLabel = document.getElementById('donut-center-label');
    var centerSub = document.getElementById('donut-center-sub');
    
    if (!donutSvg || !centerMetric || !centerLabel || !centerSub) {
        return;
    }

    var defaultMetric = centerMetric.textContent;
    var defaultLabel = centerLabel.textContent;
    var defaultSub = centerSub.textContent;
    var defaultColor = centerMetric.getAttribute('data-default-color') || 'var(--color-text)';

    var slices = document.querySelectorAll('.donut-slice');
    var legendRows = document.querySelectorAll('.dashboard-legend-row');

    function highlightBrand(brandId, brandName, count, pct, color) {
        if (centerMetric) {
            centerMetric.textContent = pct + '%';
            centerMetric.style.fill = color;
        }
        if (centerLabel) {
            centerLabel.textContent = brandName;
        }
        if (centerSub) {
            centerSub.textContent = count + (parseInt(count, 10) === 1 ? ' Model' : ' Models');
        }

        slices.forEach(function(s) {
            if (s.getAttribute('data-brand-id') === String(brandId)) {
                s.classList.add('is-active');
            } else {
                s.classList.add('is-dimmed');
                s.classList.remove('is-active');
            }
        });

        legendRows.forEach(function(r) {
            if (r.getAttribute('data-brand-id') === String(brandId)) {
                r.classList.add('is-active');
            } else {
                r.classList.remove('is-active');
            }
        });
    }

    function resetHighlight() {
        if (centerMetric) {
            centerMetric.textContent = defaultMetric;
            centerMetric.style.fill = defaultColor;
        }
        if (centerLabel) {
            centerLabel.textContent = defaultLabel;
        }
        if (centerSub) {
            centerSub.textContent = defaultSub;
        }

        slices.forEach(function(s) {
            s.classList.remove('is-active');
            s.classList.remove('is-dimmed');
        });

        legendRows.forEach(function(r) {
            r.classList.remove('is-active');
        });
    }

    // Attach listeners to SVG slices
    slices.forEach(function(slice) {
        var brandId = slice.getAttribute('data-brand-id');
        var brandName = slice.getAttribute('data-brand-name');
        var count = slice.getAttribute('data-count');
        var pct = slice.getAttribute('data-pct');
        var color = slice.getAttribute('data-color');
        var url = slice.getAttribute('data-url');

        slice.addEventListener('mouseenter', function() {
            highlightBrand(brandId, brandName, count, pct, color);
        });

        slice.addEventListener('mouseleave', function() {
            resetHighlight();
        });

        slice.addEventListener('click', function() {
            if (url) {
                window.location.href = url;
            }
        });
    });

    // Attach listeners to Legend rows
    legendRows.forEach(function(row) {
        var brandId = row.getAttribute('data-brand-id');
        var brandName = row.getAttribute('data-brand-name');
        var count = row.getAttribute('data-count');
        var pct = row.getAttribute('data-pct');
        var color = row.getAttribute('data-color');

        row.addEventListener('mouseenter', function() {
            highlightBrand(brandId, brandName, count, pct, color);
        });

        row.addEventListener('mouseleave', function() {
            resetHighlight();
        });
    });
});
