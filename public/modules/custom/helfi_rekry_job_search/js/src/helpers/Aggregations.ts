import { AggregationItem } from '../types/Aggregations';

// Transform Elastic aggregation bucket to key - value map
export const bucketToMap = (bucket: AggregationItem[] = []) => {
  const result = new Map();

  for (const item of bucket) {
    result.set(item.key, item.doc_count);
  }

  return result;
};
