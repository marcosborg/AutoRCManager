const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

async function run({total = 2001, fail = false, change = false, client = false} = {}) {
  let init, button, blob, warning, calls = [];
  const node = {data(k, v) { if (arguments.length === 1) return this[k]; this[k] = v; return this; }, prop() {return this;}, text() {return this;}};
  const document = {body: {appendChild() {}}, createElement() {return {click() {}, remove() {}};}};
  const rows = Array.from({length: total}, (_, id) => ({id, name: id === 0 ? '=SUM(A1)' : 'Row '+id, hidden: 'Extra '+id}));
  function $(value) {return value === document ? {on(event, cb) {init = cb;}} : node;}
  $.extend = (...args) => Object.assign({}, ...args.filter(x => typeof x === 'object'));
  $.ajax = async ({data}) => {
    calls.push(structuredClone(data));
    if (fail && data.start > 0) throw Error('network');
    return {recordsFiltered: change && data.start > 0 ? total + 1 : total, data: rows.slice(data.start, data.start + data.length)};
  };
  const api = {button() {return {add(i, value) {button = value;}};}, ajax: {params() {return {search: {value: 'filter'}, order: [{column: 0, dir: 'asc'}]};}}, rows() {return {data() {return {toArray() {return rows;}};}};}};
  $.fn = {dataTable: {Api: function () {return api;}}};
  const settings = {_buttons: [{}], ajax: '/list?amount_min=10', oFeatures: {bServerSide: !client}, aoColumns:
    ['name', 'hidden', 'actions'].map(mData => ({mData, sTitle: mData, fnGetData(row) {return row[mData];}}))};
  const context = {jQuery: $, document, Set, Blob, URL: {createObjectURL(value) {blob = value; return 'blob:test';}, revokeObjectURL() {}},
    DOMParser: class {parseFromString(value) {return {body: {textContent: value.replace(/<[^>]*>/g, '')}};}},
    alert(value) {warning = value;}, setTimeout() {}};
  vm.runInNewContext(fs.readFileSync('public/js/complete-list-export.js', 'utf8'), context);
  init({}, settings);
  await button.action(null, api, node);
  return {text: blob ? await blob.text() : null, warning, calls, node};
}
test('exports all 2001 rows, including hidden data, preserving filters and neutralizing formulas', async () => {
  const result = await run();
  assert.equal(result.calls.length, 3);
  assert.equal(result.calls[1].start, 1000);
  assert.equal(result.calls[1].search.value, 'filter');
  assert.equal(result.text.split('\r\n').length, 2002);
  assert.match(result.text, /Extra 2000/);
  assert.match(result.text, /'=SUM/);
  assert.doesNotMatch(result.text, /actions/);
  assert.equal(result.node.exporting, false);
});
test('network failure never downloads a partial file', async () => {
  const result = await run({fail: true});
  assert.equal(result.text, null); assert.ok(result.warning);
});
test('changed result counts abort rather than silently omit rows', async () => {
  const result = await run({change: true});
  assert.equal(result.text, null); assert.ok(result.warning);
});
test('empty and client-side results also export', async () => {
  assert.ok((await run({total: 0})).text);
  const result = await run({total: 120, client: true});
  assert.equal(result.calls.length, 0);
  assert.equal(result.text.split('\r\n').length, 121);
});
