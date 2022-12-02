export const AGGREGATIONS = {
  aggs: {
    occupations: {
      terms: {
        field: 'field_task_area.keyword',
      },
    },
  },
};
