import IndexFields from '../enum/IndexFields';

const now = Math.floor(Date.now() / 1000);

const languageFilter = {
  term: { [IndexFields.LANGUAGE]: window.drupalSettings.path.currentLanguage || 'fi' },
};

export const FILTER = {
  filter: [
    { ...languageFilter },
    { term: { [IndexFields.ENTITY_TYPE]: 'node' } },
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
    employment: {
      terms: {
        field: 'employment_id',
      },
    },
    employment_type: {
      terms: {
        field: 'employment_type_id',
      },
    },
  },
  query: {
    bool: { ...FILTER },
  },
};

export const EMPLOYMENT_FILTER_OPTIONS = {
  query: {
    bool: {
      should: [
        {
          terms: { tid: [1, 3, 5, 6, 7, 10] },
        },
      ],
      filter: [{ ...languageFilter }, { term: { [IndexFields.ENTITY_TYPE]: 'taxonomy_term' } }],
      minimum_should_match: 1,
    },
  },
};
