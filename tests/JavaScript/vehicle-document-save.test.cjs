const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/views/admin/vehicles/edit.blade.php', 'utf8');
function setup() {
    const requests = [], alerts = [], rows = [];
    const wrap = p => {
        p.done = fn => { p.then(fn, () => {}); return p; };
        p.fail = fn => { p.catch(fn); return p; };
        p.always = fn => { p.then(fn, fn); return p; };
        const then = p.then.bind(p);
        p.then = (...args) => wrap(then(...args));
        return p;
    };
    const $ = () => ({on(){}, replaceWith(){}, text(){}});
    $.ajax = options => { let resolve, reject; const promise = wrap(new Promise((a,b) => {resolve=a; reject=b;})); requests.push({options,resolve,reject}); return promise; };
    const form = {action:'/vehicle', querySelectorAll:()=>rows, querySelector:()=>({value:'csrf'}), addEventListener:(name,fn)=>{form.submit=fn;}};
    const context = { $, form, Map, Array, document:{}, window:{}, isSubmitting:false, VehiclePhotoUploads:{blockReason:()=>null}, setLoadingState(){}, showAjaxAlert:(...args)=>alerts.push(args), refreshPaymentsPanels:()=>wrap(Promise.resolve()), clearPaymentEntryFields(){}, refreshAcquisitionExpensesTotal(){}, FormData:class {get(){return null;}} };
    vm.createContext(context);
    const functions = source.slice(source.indexOf('        const documentSaves ='), source.indexOf('        function refreshPaymentsPanels()'));
    const submit = source.slice(source.indexOf("        form.addEventListener('submit', async"),source.indexOf("        document.addEventListener('click', function (event) {", source.indexOf("        form.addEventListener('submit', async"))).replace('@json(\\App\\Models\\Vehicle::DESTINATION_COMPANIES)', '{}');
    vm.runInContext(functions+'\n'+submit, context);
    function row(type='dua') {
        const select={value:type}, button={dataset:{url:'/type'}}, status={};
        const r={dataset:{savedType:'unknown',revision:'old'},querySelector:s=>s==='.js-document-type'?select:s==='.js-save-document-type'?button:status}; rows.push(r); return {r,select,button,status};
    }
    const submitForm = () => form.submit({preventDefault(){}});
    return {row,requests,alerts,submitForm,save: r=>context.saveDocumentType(r)};
}
const tick = () => new Promise(resolve=>setImmediate(resolve));
const saved = type=>({type,revision:'new',message:'Guardado',summary_html:''});
test('general save persists each pending type before posting the vehicle', async()=>{
    const s=setup(); const a=s.row('dua'), b=s.row('inspection'); const pending=s.submitForm();
    assert.equal(s.requests.length,1); assert.equal(s.requests[0].options.data.type,'dua');
    s.requests[0].resolve(saved('dua')); await tick();
    assert.equal(s.requests.length,2); assert.equal(s.requests[1].options.data.type,'inspection');
    s.requests[1].resolve(saved('inspection')); await pending;
    assert.equal(s.requests[2].options.method,'POST');
    assert.equal(a.r.dataset.savedType,'dua'); assert.equal(b.r.dataset.savedType,'inspection');
    s.requests[2].resolve({}); await tick(); assert.equal(a.select.disabled,false);
});
test('failed classification retains choices, stops general save and retries only unsaved types', async()=>{
    const s=setup(); const a=s.row('dua'), b=s.row('inspection'); const pending=s.submitForm();
    s.requests[0].resolve(saved('dua')); await tick();
    s.requests[1].reject({status:409,responseJSON:{message:'Conflito'}}); await pending;
    assert.equal(s.requests.length,2); assert.equal(b.select.value,'inspection'); assert.equal(b.status.textContent,'Conflito'); assert.equal(b.select.disabled,false);
    assert.match(s.alerts[0][1],/ficha não foi gravada/);
    const retry=s.submitForm(); assert.equal(s.requests[2].options.data.type,'inspection');
    s.requests[2].resolve(saved('inspection')); await retry; s.requests[3].resolve({}); await tick();
});
test('general save waits for an already running per-file save without duplicating it', async()=>{
    const s=setup(); const a=s.row(); s.save(a.r); const pending=s.submitForm(); s.submitForm();
    assert.equal(s.requests.length,1); s.requests[0].resolve(saved('dua')); await pending;
    assert.equal(s.requests.length,2); assert.equal(s.requests[1].options.method,'POST');
    s.requests[1].resolve({}); await tick();
});
