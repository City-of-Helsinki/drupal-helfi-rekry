import IndexFields from '../enum/IndexFields';

const now = Math.floor(Date.now() / 1000);

export const FILTER = {
  filter: [
    { term: { [IndexFields.LANGUAGE]: window.drupalSettings.path.currentLanguage || 'fi' } },
    {
      range: {
        [IndexFields.UNPUBLISH_ON]: {
          gte: now,
        },
      },
    },
  ],
};

export const AGGREGATIONS = {
  aggs: {
    occupations: {
      terms: {
        field: 'field_task_area.keyword',
      },
    },
  },
  query: {
    bool: { ...FILTER },
  },
};
