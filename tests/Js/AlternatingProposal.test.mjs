import assert from 'node:assert/strict';
import test from 'node:test';
import { getAlternatingProposal } from '../../web_src/js/vue/speech/AlternatingProposal.js';

const queue = (id, names) => ({ id, applied: names.map(name => ({ name })) });
const slot = (id, isPointOfOrder = false) => ({ subqueue: { id }, is_point_of_order: isPointOfOrder });

test('resumes alternation after three men without making up missed turns', () => {
  const women = queue(1, ['W1', 'W2']);
  const men = queue(2, ['M4', 'M5']);
  const history = [slot(2), slot(2), slot(2)];

  for (const [expected, id] of [['W1', 1], ['M4', 2], ['W2', 1], ['M5', 2]]) {
    const proposed = getAlternatingProposal([women, men], history);
    assert.equal(proposed?.name, expected);
    const selectedQueue = id === 1 ? women : men;
    selectedQueue.applied.shift();
    history.push(slot(id));
  }
});

test('skips empty subqueues in a three-way rotation', () => {
  const queues = [queue(1, []), queue(2, []), queue(3, ['C'])];
  assert.equal(getAlternatingProposal(queues, []), queues[2].applied[0]);
  assert.equal(getAlternatingProposal(queues, [slot(3)]), queues[2].applied[0]);
  queues[0].applied.push({ name: 'A' });
  assert.equal(getAlternatingProposal(queues, [slot(3)]), queues[0].applied[0]);
  assert.equal(getAlternatingProposal(queues, [slot(1)]), queues[2].applied[0]);
  queues[2].applied.shift();
  assert.equal(getAlternatingProposal(queues, [slot(1)]), queues[0].applied[0]);
  queues[0].applied.shift();
  assert.equal(getAlternatingProposal(queues, [slot(1)]), null);
});

test('a point of order does not consume an alternating turn', () => {
  const queues = [queue(1, ['W']), queue(2, ['M'])];
  assert.equal(getAlternatingProposal(queues, [slot(1), slot(2, true)]), queues[1].applied[0]);
});
