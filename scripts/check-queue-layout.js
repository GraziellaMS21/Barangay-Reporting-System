/**
 * Layout and overflow verification script for Report Queue.
 * Can be run in browser DevTools console on /admin/reports.
 */
(function verifyQueueLayout() {
  const table = document.querySelector('.data-table-queue');
  const results = {
    innerWidth: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    hasHorizontalOverflow: document.documentElement.scrollWidth > window.innerWidth,
    tableWidth: table ? table.offsetWidth : null,
    rowCount: document.querySelectorAll('.data-table-queue tbody tr:not(.table-empty)').length,
    density: localStorage.getItem('queue_density') || 'compact',
  };
  
  console.table(results);
  
  if (results.hasHorizontalOverflow) {
    console.warn('⚠️ Horizontal overflow detected at current width!');
  } else {
    console.info('✅ Zero horizontal overflow: page fits perfectly within viewport.');
  }
  
  return results;
})();
