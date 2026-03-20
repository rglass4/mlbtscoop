</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('[data-sort-table]').forEach(function(table) {
    table.querySelectorAll('th[data-sort]').forEach(function(header, index) {
      header.addEventListener('click', function() {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const asc = header.dataset.order !== 'asc';
        table.querySelectorAll('th[data-sort]').forEach(function(th) { delete th.dataset.order; });
        header.dataset.order = asc ? 'asc' : 'desc';
        rows.sort(function(a, b) {
          const av = a.children[index].innerText.trim();
          const bv = b.children[index].innerText.trim();
          const an = parseFloat(av.replace(/[^0-9.-]/g, ''));
          const bn = parseFloat(bv.replace(/[^0-9.-]/g, ''));
          const numeric = !Number.isNaN(an) && !Number.isNaN(bn);
          const result = numeric ? an - bn : av.localeCompare(bv);
          return asc ? result : -result;
        });
        rows.forEach(function(row) { tbody.appendChild(row); });
      });
    });
  });
</script>
</body>
</html>
