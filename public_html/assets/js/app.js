(function(){
  function init(){
    const addBtn = document.getElementById('addItem');
    const productSelect = document.getElementById('productSelect');
    const itemsTable = document.querySelector('#itemsTable tbody');
    const itemsInput = document.getElementById('itemsInput');
    const totalDisplay = document.getElementById('totalDisplay');
    if(!addBtn || !productSelect || !itemsTable || !itemsInput || !totalDisplay) return;
    let items = [];
    function formatMoney(v){ return 'R$ '+v.toFixed(2).replace('.',','); }
    function render(){
      itemsTable.innerHTML=''; let total=0;
      items.forEach((it,idx)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="p-2">${it.name}</td><td class="p-2"><input type="number" value="${it.qty}" min="1" data-idx="${idx}" class="qty border p-1 w-20"></td><td class="p-2">${formatMoney(it.price)}</td><td class="p-2">${formatMoney(it.price*it.qty)}</td><td class="p-2"><button data-idx="${idx}" class="remove bg-red-100 text-red-700 px-2 py-1 rounded">Remover</button></td>`;
        itemsTable.appendChild(tr);
        total += it.price*it.qty;
      });
      totalDisplay.textContent = formatMoney(total);
      itemsInput.value = JSON.stringify(items.map(i=>({product_id:i.id,qty:i.qty,price:i.price})));
    }
    addBtn.addEventListener('click', ()=>{
      const v = productSelect.value; if(!v) return; const [id,price] = v.split('|'); const name = productSelect.options[productSelect.selectedIndex].text;
      items.push({id:parseInt(id),name,price:parseFloat(price),qty:1}); render();
    });
    document.addEventListener('click', function(e){
      if(e.target.classList.contains('remove')){ const idx = e.target.dataset.idx; items.splice(idx,1); render(); }
    });
    document.addEventListener('change', function(e){ if(e.target.classList.contains('qty')){ const idx=e.target.dataset.idx; items[idx].qty = parseInt(e.target.value)||1; render(); }});
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
