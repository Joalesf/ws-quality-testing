(() => {
 const table=document.querySelector('#detail-table');
 if(!table)return;
 const body=table.tBodies[0],cols=table.tHead.rows[0].cells.length-1;
 function row(values=[]) { const tr=document.createElement('tr');for(let j=0;j<cols;j++){const td=tr.insertCell(),input=document.createElement('input');input.value=values[j]??'';input.maxLength=2000;input.setAttribute('aria-label',table.tHead.rows[0].cells[j].textContent);td.append(input);}const td=tr.insertCell(),b=document.createElement('button');b.type='button';b.className='remove-row';b.textContent='×';b.setAttribute('aria-label','Quitar fila');td.append(b);body.append(tr);names(); }
 function names(){[...body.rows].forEach((tr,i)=>tr.querySelectorAll('input').forEach(input=>input.name=`rows[${i}][]`));}
 document.querySelector('#add-row').onclick=()=>{if(body.rows.length<100)row();};
 body.onclick=e=>{if(e.target.closest('.remove-row')&&body.rows.length>1){e.target.closest('tr').remove();names();}};
 const field=n=>document.querySelector(`[name="${n}"]`);
 document.querySelector('#generate-boundaries')?.addEventListener('click',()=>{const a=Number(field('minimum').value),b=Number(field('maximum').value),s=Number(field('step').value);if(!field('minimum').value||!field('maximum').value||!Number.isFinite(a+b+s)||a>b||s<=0){document.querySelector('#calculation').textContent='Indica un intervalo válido y un incremento positivo.';return;}body.replaceChildren();[[a-s,'Debajo del mínimo','Rechazar'],[a,'Mínimo','Aceptar'],[a+s,'Sobre el mínimo',a+s<=b?'Aceptar':'Rechazar'],[b-s,'Bajo el máximo',b-s>=a?'Aceptar':'Rechazar'],[b,'Máximo','Aceptar'],[b+s,'Sobre el máximo','Rechazar']].forEach(v=>row([Number(v[0].toPrecision(12)),v[1],v[2], '']));});
 document.addEventListener('input',()=>{if(field('total')){const total=Number(field('total').value),covered=Number(field('covered').value);document.querySelector('#calculation').textContent=total>0&&covered>=0&&covered<=total?`Cobertura: ${(100*covered/total).toFixed(2)} %`:'Revisa el total y los elementos ejecutados.';}});
 document.querySelector('input[type=file]')?.addEventListener('change',e=>{document.querySelector('#file-preview').textContent=[...e.target.files].map(f=>`${f.name} (${(f.size/1024).toFixed(0)} KB)`).join(' · ');});
})();
