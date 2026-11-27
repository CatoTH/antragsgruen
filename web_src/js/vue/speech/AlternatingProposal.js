/** Suggest the next applicant without making up turns missed while a subqueue was empty. */
export function getAlternatingProposal(subqueues, slots) {
  if (subqueues.length === 0) {
    return null;
  }

  // Slots are ordered by position by the API. A point of order does not consume a turn.
  const lastOrdinarySlot = slots.filter(slot => !slot.is_point_of_order).at(-1);
  // The API uses null for a slot in the automatic subqueue, but -1 for its placeholder.
  const lastIndex = lastOrdinarySlot
    ? subqueues.findIndex(subqueue => subqueue.id === (lastOrdinarySlot.subqueue.id ?? -1))
    : -1;
  const firstIndex = (lastIndex + 1) % subqueues.length;

  for (let offset = 0; offset < subqueues.length; offset++) {
    const subqueue = subqueues[(firstIndex + offset) % subqueues.length];
    if (subqueue.applied.length > 0) {
      return subqueue.applied[0];
    }
  }

  return null;
}
