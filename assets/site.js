/* 4268 land dots, Natural Earth 110m (public domain) */
const LAND_DOTS="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";
/* TF International — site.js (shared by all languages; texts come from data-* attributes in the HTML) */
const reduceMotion=matchMedia('(prefers-reduced-motion: reduce)').matches;

/* hero title: split into animated words */
(()=>{
  const h=document.getElementById('heroTitle');if(!h)return;
  let d=0;
  h.innerHTML=h.dataset.lines.split('|').map(line=>line.split(' ').map(w=>{d+=.07;return `<span class="w" aria-hidden="true"><span style="animation-delay:${d.toFixed(2)}s">${w}</span></span>`}).join(' ')).join('<br>')
    +`<span class="sr-only">${h.dataset.lines.replace('|',' ')}</span>`;
})();

/* nav */
(()=>{
  const nav=document.getElementById('nav'),b=document.getElementById('burger'),m=document.getElementById('menu');
  const onS=()=>nav.classList.toggle('solid',scrollY>40);onS();addEventListener('scroll',onS,{passive:true});
  if(!b)return;
  const setOpen=o=>{m.classList.toggle('open',o);b.setAttribute('aria-expanded',o);b.setAttribute('aria-label',o?b.dataset.close:b.dataset.open);document.body.classList.toggle('locked',o)};
  b.addEventListener('click',()=>setOpen(!m.classList.contains('open')));
  m.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{
    if(!m.classList.contains('open'))return;
    e.preventDefault();setOpen(false);
    const t=document.querySelector(a.getAttribute('href'));
    history.replaceState(null,'',a.getAttribute('href'));
    setTimeout(()=>t&&t.scrollIntoView({behavior:reduceMotion?'auto':'smooth'}),10);
  }));
  addEventListener('keydown',e=>{if(e.key==='Escape')setOpen(false)});
  matchMedia('(min-width:1021px)').addEventListener('change',e=>{if(e.matches)setOpen(false)});
})();

/* globe — land dots, atmosphere, comet arcs, inertia drag, hover labels; pauses when off-screen */
(()=>{
  const cv=document.getElementById('globe');if(!cv)return;
  const ctx=cv.getContext('2d'),wrap=document.getElementById('globeWrap'),homeTag=document.getElementById('tagPlzen');
  const cityTag=document.createElement('span');cityTag.className='tag city';cityTag.innerHTML='<i></i><span></span>';wrap.appendChild(cityTag);
  const R2D=Math.PI/180;
  const toV=(lat,lon)=>{const la=lat*R2D,lo=lon*R2D;return[Math.cos(la)*Math.sin(lo),Math.sin(la),Math.cos(la)*Math.cos(lo)]};
  const dot3=(a,b)=>a[0]*b[0]+a[1]*b[1]+a[2]*b[2];
  const slerp=(a,b,t)=>{const d=Math.acos(Math.min(1,dot3(a,b))),s=Math.sin(d);if(s<1e-6)return a;const k1=Math.sin((1-t)*d)/s,k2=Math.sin(t*d)/s;return[a[0]*k1+b[0]*k2,a[1]*k1+b[1]*k2,a[2]*k1+b[2]*k2]};

  // land dots (lat/lon * 100 as Int16 pairs, base64)
  const raw=atob(LAND_DOTS),buf=new Int16Array(raw.length/2);
  for(let i=0;i<buf.length;i++)buf[i]=raw.charCodeAt(2*i)|(raw.charCodeAt(2*i+1)<<8);
  const land=[];for(let i=0;i<buf.length;i+=2)land.push(toV(buf[i]/100,buf[i+1]/100));

  const H=toV(49.74,13.37);
  const cities=[['Berlin',52.52,13.40],['Stuttgart',48.78,9.18],['München',48.14,11.58],['Paris',48.86,2.35],['London',51.51,-0.13],['Milano',45.46,9.19],['Toulouse',43.60,1.44],['Warszawa',52.23,21.01],['Detroit',42.33,-83.05],['Shanghai',31.23,121.47],['Budapest',47.50,19.04],['Madrid',40.42,-3.70],['Stockholm',59.33,18.07]];
  const arcs=cities.map(([name,la,lo],i)=>{
    const to=toV(la,lo),dist=Math.min(1,Math.acos(Math.min(1,dot3(H,to)))/1.2),n=Math.max(24,Math.round(64*dist)),pts=[];
    for(let k=0;k<=n;k++){const t=k/n,v=slerp(H,to,t),l=1+0.2*Math.sin(Math.PI*t)*dist;pts.push([v[0]*l,v[1]*l,v[2]*l])}
    return{name,to,pts,off:i*0.61,speed:0.22+0.12*(1-dist)};
  });

  let rotY=-13.37*R2D+0.3,rotX=0.62,tiltX=0,tiltTarget=0,velY=0.0011,W=0,dpr=1,drag=null,running=false,visible=true,raf=0,hover=null,last=0;
  const AUTO=0.0011;
  function size(){dpr=Math.min(devicePixelRatio||1,2);W=wrap.clientWidth;cv.width=W*dpr;cv.height=W*dpr}
  size();addEventListener('resize',()=>{size();if(!running)draw(performance.now())});
  let cy_,sy_,cx_,sx_;
  const prep=()=>{cy_=Math.cos(rotY);sy_=Math.sin(rotY);const rx=rotX+tiltX;cx_=Math.cos(rx);sx_=Math.sin(rx)};
  const rot=v=>{const x=v[0]*cy_+v[2]*sy_,z0=-v[0]*sy_+v[2]*cy_;return[x,v[1]*cx_-z0*sx_,v[1]*sx_+z0*cx_]};

  function draw(t){
    prep();
    const s=W*dpr,c=s/2,r=s*0.38,time=t/1000;
    ctx.clearRect(0,0,s,s);
    // atmosphere
    let g=ctx.createRadialGradient(c,c,r*0.92,c,c,r*1.32);
    g.addColorStop(0,'rgba(64,120,255,0.38)');g.addColorStop(0.18,'rgba(47,107,255,0.16)');g.addColorStop(1,'rgba(47,107,255,0)');
    ctx.fillStyle=g;ctx.beginPath();ctx.arc(c,c,r*1.32,0,7);ctx.fill();
    // sphere body, lit from upper left
    g=ctx.createRadialGradient(c-r*0.45,c-r*0.5,r*0.1,c,c,r);
    g.addColorStop(0,'#16254A');g.addColorStop(0.55,'#0B1430');g.addColorStop(1,'#060B1C');
    ctx.fillStyle=g;ctx.beginPath();ctx.arc(c,c,r,0,7);ctx.fill();
    // rim light
    ctx.strokeStyle='rgba(150,185,255,0.35)';ctx.lineWidth=1.2*dpr;ctx.beginPath();ctx.arc(c,c,r,0,7);ctx.stroke();
    // land
    const L=[-0.45,0.5,0.75];
    for(const d of land){
      const p=rot(d);if(p[2]<0.02)continue;
      const lit=Math.max(0,p[0]*L[0]+p[1]*L[1]+p[2]*L[2]);
      const a=Math.min(1,0.18+0.55*p[2]+0.35*lit);
      ctx.fillStyle=`rgba(${170+60*lit|0},${200+40*lit|0},255,${a.toFixed(3)})`;
      const sz=(1.1+p[2]*1.3)*dpr;ctx.fillRect(c+p[0]*r-sz/2,c-p[1]*r-sz/2,sz,sz);
    }
    // arcs + comets
    for(const a of arcs){
      const P=a.pts.map(rot),n=P.length-1;
      ctx.lineWidth=1*dpr;
      for(let k=0;k<n;k++){const p=P[k],q=P[k+1];if(p[2]<0&&q[2]<0)continue;ctx.strokeStyle=`rgba(90,140,255,${p[2]<0?0.06:0.22})`;ctx.beginPath();ctx.moveTo(c+p[0]*r,c-p[1]*r);ctx.lineTo(c+q[0]*r,c-q[1]*r);ctx.stroke()}
      const ph=(time*a.speed+a.off)%1.6;
      if(ph<=1){
        const head=ph*n,tail=Math.max(0,head-n*0.28);
        for(let k=Math.floor(tail);k<Math.min(n,head);k++){const p=P[k],q=P[k+1];if(p[2]<0)continue;const f=(k-tail)/(head-tail||1);
          ctx.strokeStyle=`rgba(${150+105*f|0},${190+65*f|0},255,${(f*0.95).toFixed(3)})`;ctx.lineWidth=(0.8+1.8*f)*dpr;ctx.beginPath();ctx.moveTo(c+p[0]*r,c-p[1]*r);ctx.lineTo(c+q[0]*r,c-q[1]*r);ctx.stroke()}
        const hp=P[Math.min(n,Math.floor(head))];
        if(hp[2]>0){ctx.save();ctx.shadowColor='rgba(120,170,255,1)';ctx.shadowBlur=12*dpr;ctx.fillStyle='#fff';ctx.beginPath();ctx.arc(c+hp[0]*r,c-hp[1]*r,2.4*dpr,0,7);ctx.fill();ctx.restore()}
      }else if(ph<1.25){ // arrival ripple
        const e=P[n],k=(ph-1)/0.25;if(e[2]>0){ctx.strokeStyle=`rgba(120,170,255,${(1-k).toFixed(3)})`;ctx.lineWidth=1.4*dpr;ctx.beginPath();ctx.arc(c+e[0]*r,c-e[1]*r,(3+k*14)*dpr,0,7);ctx.stroke()}
      }
      const e=rot(a.to);a.screen=e[2]>0?[(c+e[0]*r)/dpr,(c-e[1]*r)/dpr]:null;
      if(e[2]>0){ctx.fillStyle=hover===a?'#fff':'rgba(80,135,255,0.95)';ctx.beginPath();ctx.arc(c+e[0]*r,c-e[1]*r,(hover===a?3.6:2.6)*dpr,0,7);ctx.fill()}
    }
    // home: Plzeň
    const h=rot(H);
    if(h[2]>0){
      const x=c+h[0]*r,y=c-h[1]*r;
      for(const off of [0,0.5]){const pr=((time+off*2)%2)/2;ctx.strokeStyle=`rgba(80,140,255,${(1-pr)*0.9})`;ctx.lineWidth=1.5*dpr;ctx.beginPath();ctx.arc(x,y,(4+pr*22)*dpr,0,7);ctx.stroke()}
      ctx.save();ctx.shadowColor='rgba(140,180,255,1)';ctx.shadowBlur=16*dpr;ctx.fillStyle='#fff';ctx.beginPath();ctx.arc(x,y,4.2*dpr,0,7);ctx.fill();ctx.restore();
      homeTag.style.opacity=1;homeTag.style.left=(x/dpr+14)+'px';homeTag.style.top=(y/dpr-44)+'px';
    }else homeTag.style.opacity=0;
    if(hover&&hover.screen){cityTag.style.opacity=1;cityTag.style.left=(hover.screen[0]+12)+'px';cityTag.style.top=(hover.screen[1]+10)+'px'}else cityTag.style.opacity=0;
  }

  function frame(t){
    const dt=Math.min(3,(t-(last||t))/16.7);last=t;
    if(!drag){velY+=(AUTO-velY)*0.02*dt;rotY+=velY*dt}
    tiltX+=(tiltTarget-tiltX)*0.06*dt;
    draw(t);raf=requestAnimationFrame(frame);
  }
  function sync(){const want=visible&&!document.hidden&&!reduceMotion;if(want&&!running){running=true;last=0;raf=requestAnimationFrame(frame)}else if(!want&&running){running=false;cancelAnimationFrame(raf)}}
  new IntersectionObserver(es=>{visible=es[0].isIntersecting;sync()}).observe(cv);
  document.addEventListener('visibilitychange',sync);
  draw(0);sync();

  // interaction: drag with inertia, gentle tilt towards the pointer, city labels on hover
  let lastX=0,lastT=0;
  cv.addEventListener('pointerdown',e=>{drag={x:e.clientX,y:e.clientY,ry:rotY,rx:rotX};lastX=e.clientX;lastT=performance.now();cv.setPointerCapture(e.pointerId)});
  cv.addEventListener('pointermove',e=>{
    const b=cv.getBoundingClientRect(),mx=e.clientX-b.left,my=e.clientY-b.top;
    if(drag){
      rotY=drag.ry+(e.clientX-drag.x)*0.006;rotX=Math.max(-0.9,Math.min(1.1,drag.rx+(e.clientY-drag.y)*0.006));
      const now=performance.now();velY=(e.clientX-lastX)*0.006/Math.max(1,(now-lastT)/16.7);lastX=e.clientX;lastT=now;
      if(!running)draw(now);return;
    }
    if(e.pointerType==='mouse')tiltTarget=((my/b.height)-0.5)*0.25;
    let best=null,bd=16;for(const a of arcs){if(!a.screen)continue;const d=Math.hypot(a.screen[0]-mx,a.screen[1]-my);if(d<bd){bd=d;best=a}}
    if(best!==hover){hover=best;if(best)cityTag.lastChild.textContent=best.name}
    cv.style.cursor=hover?'pointer':'';
  });
  const up=()=>{drag=null};cv.addEventListener('pointerup',up);cv.addEventListener('pointercancel',up);
  cv.addEventListener('pointerleave',()=>{tiltTarget=0;hover=null});
})();

/* scroll-lit text (words marked * are highlighted) */
(()=>{
  const el=document.getElementById('revealText');if(!el)return;
  el.innerHTML=el.dataset.src.split(/\s+/).map(w=>w.startsWith('*')?`<span class="wd hl">${w.slice(1)}</span>`:`<span class="wd">${w}</span>`).join(' ');
  const words=[...el.querySelectorAll('.wd')];
  const upd=()=>{const r=el.getBoundingClientRect(),vh=innerHeight;const p=Math.min(1,Math.max(0,(vh*0.85-r.top)/(r.height+vh*0.35)));const n=Math.round(p*words.length);words.forEach((w,i)=>w.classList.toggle('on',i<n))};
  upd();addEventListener('scroll',upd,{passive:true});addEventListener('resize',upd);
})();

/* reveal on scroll */
(()=>{
  const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}}),{threshold:.15});
  document.querySelectorAll('.rv,#outcomes').forEach(el=>io.observe(el));
})();

/* stacked service cards */
(()=>{
  const cards=[...document.querySelectorAll('.card')];if(!cards.length)return;
  const upd=()=>{if(innerWidth<=860){cards.forEach(c=>{c.style.transform='';c.style.filter=''});return}
    cards.forEach((c,i)=>{const next=cards[i+1];if(!next){c.style.transform='';return}
      const d=next.getBoundingClientRect().top-c.getBoundingClientRect().top,p=Math.min(1,Math.max(0,1-d/c.offsetHeight));
      c.style.transform=`scale(${1-p*0.05})`;c.style.filter=`brightness(${1-p*0.25})`})};
  upd();addEventListener('scroll',upd,{passive:true});addEventListener('resize',upd);
})();

/* office hours status (Europe/Prague, Mon–Fri 8–16) */
(()=>{
  const el=document.getElementById('status');if(!el)return;
  const f=new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Prague',weekday:'short',hour:'2-digit',minute:'2-digit',hour12:false});
  const p=Object.fromEntries(f.formatToParts(new Date()).map(x=>[x.type,x.value]));
  const h=+p.hour+ +p.minute/60,open=!['Sat','Sun'].includes(p.weekday)&&h>=8&&h<16;
  el.classList.toggle('off',!open);
  el.querySelector('span').textContent=open?el.dataset.open:el.dataset.closed;
})();

/* inquiry form -> /api/poptavka.php (stored for the admin) */
(()=>{
  const form=document.getElementById('inq');if(!form)return;
  const ok=document.getElementById('inqOk'),btn=form.querySelector('button[type=submit]'),btnLabel=btn.querySelector('span'),msg=form.querySelector('.form-msg');
  const hint=form.querySelector('.crisis-hint'),E=form.dataset,label0=btnLabel.textContent;
  const showOk=()=>{form.hidden=true;ok.hidden=false;ok.focus()};

  // back from a no-JS submit (?poptavka=ok)
  if(new URLSearchParams(location.search).get('poptavka')==='ok')showOk();

  // token proves a real browser loaded the page (fetched lazily, once)
  let tokenP=null;
  const getToken=()=>tokenP||(tokenP=fetch(form.action+'?token=1',{headers:{'X-Requested-With':'fetch'}}).then(r=>r.json()).then(j=>{form.elements.ts.value=j.token}).catch(()=>{tokenP=null}));
  // fetched right after page load, so the token is always older than the server's 3 s minimum by the time a human submits
  if(document.readyState==='complete')getToken();else addEventListener('load',getToken,{once:true});
  form.addEventListener('focusin',getToken,{once:true});

  // crisis hint
  form.querySelectorAll('input[name=urgency]').forEach(r=>r.addEventListener('change',()=>{hint.hidden=form.elements.urgency.value!=='krize'}));

  const setErr=(name,code)=>{
    const box=document.getElementById('e-'+name);if(!box)return;
    box.textContent=code?(E['e'+code[0].toUpperCase()+code.slice(1)]||E.eRequired):'';
    const wrap=box.closest('.f,fieldset');if(wrap)wrap.classList.toggle('invalid',!!code);
    const input=form.elements[name];if(input&&input.setAttribute)input.setAttribute('aria-invalid',code?'true':'false');
  };
  const validate=()=>{
    const v=n=>(form.elements[n].value||'').trim(),errs={};
    if(!v('service'))errs.service='required';
    if(v('name').length<2)errs.name='required';
    if(!v('email'))errs.email='required';else if(!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v('email')))errs.email='email';
    if(v('phone')&&!/^[+()0-9 .\-\/]{6,40}$/.test(v('phone')))errs.phone='phone';
    if(v('message').length<10)errs.message='short';
    return errs;
  };
  const paint=errs=>{['service','name','email','phone','message'].forEach(n=>setErr(n,errs[n]));const first=Object.keys(errs)[0];if(first){const el=form.elements[first];(el.length?el[0]:el).focus()}};

  // re-validate a field once the user fixes it
  form.addEventListener('input',e=>{const n=e.target.name;if(!n||!document.getElementById('e-'+n)?.textContent)return;setErr(n,validate()[n])});
  form.addEventListener('change',e=>{if(e.target.name==='service')setErr('service',null)});

  form.addEventListener('submit',async e=>{
    e.preventDefault();msg.hidden=true;
    const errs=validate();paint(errs);
    if(Object.keys(errs).length){msg.textContent=E.eFix;msg.hidden=false;return}
    btn.disabled=true;btnLabel.textContent=E.sending;
    try{
      await getToken();
      const r=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'fetch','Accept':'application/json'}});
      const j=await r.json().catch(()=>({}));
      if(r.ok&&j.ok){form.reset();showOk();return}
      if(r.status===422&&j.errors){paint(j.errors);msg.textContent=E.eFix}
      else msg.textContent=r.status===429?E.eRate:E.eNet;
      msg.hidden=false;
    }catch(err){msg.textContent=E.eNet;msg.hidden=false}
    finally{btn.disabled=false;btnLabel.textContent=label0}
  });
})();
