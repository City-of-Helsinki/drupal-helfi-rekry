import IndexFields from '../enum/IndexFields';

const now = Math.floor(Date.now() / 1000);

// Filter by current language
export const languageFilter = {
  term: { [IndexFields.LANGUAGE]: window.drupalSettings.path.currentLanguage || 'fi' },
};

// Filter by current date within pub dates
export const publicationFilter = {
  range: {
    [IndexFields.UNPUBLISH_ON]: {
      gte: now,
    },
  },
};

// Filter out taxonomy terms
export const nodeFilter = {
  term: { [IndexFields.ENTITY_TYPE]: 'node' },
};

// Base aggregations
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
    bool: {
      filter: [languageFilter, nodeFilter, publicationFilter],
    },
  },
};

// Get all employment filter options
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

// Get all eligible language options
export const LANGUAGE_OPTIONS = {
  aggs: {
    languages: {
      terms: {
        field: '_language.keyword',
      },
    },
  },
  query: {
    term: {
      field_copied: false,
    },
  },
};
