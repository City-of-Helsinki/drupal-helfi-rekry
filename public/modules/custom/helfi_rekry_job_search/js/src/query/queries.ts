import { CustomIds } from '../enum/CustomTermIds';
import IndexFields from '../enum/IndexFields';

const now = Math.floor(Date.now() / 1000);

// Filter by current language
export const languageFilter = {
  term: { [IndexFields.LANGUAGE]: window.drupalSettings.path.currentLanguage || 'fi' },
};

// Match by current date within pub dates or pub date is null
export const publicationQuery = {
  bool: {
    minimum_should_match: 1,
    should: [
      {
        range: {
          [IndexFields.UNPUBLISH_ON]: {
            gte: now,
          },
        },
      },
      {
        bool: {
          must: [
            {
              term: {
                status: true,
              },
            },
          ],
          must_not: [
            {
              exists: {
                field: 'unpublish_on',
              },
            },
          ],
        },
      },
    ],
  },
};

// Filter out taxonomy terms
export const nodeFilter = {
  term: { [IndexFields.ENTITY_TYPE]: 'node' },
};

// Alphabetical sort for terms
const alphabeticallySortTerms = {
  'name.keyword': {
    order: 'asc',
  },
};

// Base aggregations
export const AGGREGATIONS = {
  aggs: {
    occupations: {
      terms: {
        field: 'task_area_id',
        size: 100,
      },
    },
    employment: {
      terms: {
        field: 'employment_id',
        size: 100,
      },
    },
    employment_type: {
      terms: {
        field: 'employment_type_id',
        size: 100,
      },
    },
    employment_search_id: {
      terms: {
        field: 'employment_search_id.keyword',
        size: 100,
      },
    },
  },
  query: {
    bool: {
      ...publicationQuery.bool,
      filter: [languageFilter, nodeFilter],
    },
  },
};

// Get all employment filter options
export const EMPLOYMENT_FILTER_OPTIONS = {
  query: {
    bool: {
      should: [
        { term: { 'field_search_id.keyword': CustomIds.FIXED_CONTRACTUAL } },
        { term: { 'field_search_id.keyword': CustomIds.FIXED_SERVICE } },
        { term: { 'field_search_id.keyword': CustomIds.PERMANENT_CONTRACTUAL } },
        { term: { 'field_search_id.keyword': CustomIds.PERMANENT_SERVICE } },
        { term: { 'field_search_id.keyword': CustomIds.TRAINING } },
        { term: { 'field_search_id.keyword': CustomIds.ALTERNATION } },
      ],
      minimum_should_match: 1,
      filter: [languageFilter, { term: { [IndexFields.ENTITY_TYPE]: 'taxonomy_term' } }],
    },
  },
  sort: [alphabeticallySortTerms],
  size: 100,
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
    bool: {
      ...publicationQuery.bool,
      filter: [
        {
          term: {
            field_copied: false,
          },
        },
        nodeFilter,
      ],
    },
  },
};

// Get all task area options
export const TASK_AREA_OPTIONS = {
  query: {
    bool: {
      filter: [
        {
          term: {
            'vid.keyword': 'task_area',
          },
        },
        {
          term: {
            entity_type: 'taxonomy_term',
          },
        },
        languageFilter,
      ],
    },
  },
  sort: [alphabeticallySortTerms],
  size: 100,
};
