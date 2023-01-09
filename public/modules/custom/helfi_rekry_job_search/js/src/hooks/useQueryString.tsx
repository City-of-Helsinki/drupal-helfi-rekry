import Global from '../enum/Global';
import IndexFields from '../enum/IndexFields';
import { languageFilter, nodeFilter, publicationQuery } from '../query/queries';
import URLParams from '../types/URLParams';

/**
 * Dirty fix for combining results for multiple tids.
 * Combine results for public service / contractual employments.
 * (Virkasuhde / työsuhde)
 */
const combineEmploymentTypes = (types: (number | string)[]) => {
  if (types.includes(1) && !types.includes(2)) {
    types.push(2);
  }
  if (types.includes(3) && !types.includes(4)) {
    types.push(4);
  }

  return types;
};

const useQueryString = (urlParams: URLParams): string => {
  const { size, sortOptions } = Global;
  const page = Number.isNaN(Number(urlParams.page)) ? 1 : Number(urlParams.page);
  const must = [];
  const should = [];

  if (urlParams.keyword && urlParams.keyword.length > 0) {
    must.push({
      bool: {
        should: [
          {
            combined_fields: {
              query: urlParams.keyword.toString(),
              fields: [`${IndexFields.TITLE}^2`, IndexFields.EMPLOYMENT, IndexFields.ORGANIZATION_NAME],
            },
          },
          {
            wildcard: {
              [`${IndexFields.TITLE}.keyword`]: `*${urlParams.keyword}*`,
            },
          },
        ],
      },
    });
  }

  if (urlParams?.task_areas?.length) {
    must.push({
      terms: {
        [IndexFields.TASK_AREA_ID]: urlParams.task_areas,
      },
    });
  }

  // These values can match either employment or employment_type IDs
  if (urlParams?.employment?.length) {
    must.push({
      bool: {
        should: [
          {
            terms: {
              [IndexFields.EMPLOYMENT_ID]: urlParams.employment,
            },
          },
          {
            terms: {
              [IndexFields.EMPLOYMENT_TYPE_ID]: combineEmploymentTypes(urlParams.employment),
            },
          },
        ],
        minimum_should_match: 1,
      },
    });
  }

  if (urlParams.continuous) {
    should.push({
      term: {
        [IndexFields.CONTINUOUS]: true,
      },
    });
  }

  if (urlParams.internship) {
    should.push({
      term: {
        [IndexFields.INTERNSHIP]: true,
      },
    });
  }

  if (urlParams.summer_jobs) {
    should.push({
      term: {
        [IndexFields.SUMMER_JOB]: true,
      },
    });
  }

  if (urlParams.youth_summer_jobs) {
    should.push({
      term: {
        [IndexFields.YOUTH_SUMMER_JOB]: true,
      },
    });
  }

  const query: any = {
    bool: {
      ...publicationQuery.bool,
      filter: [
        urlParams.language
          ? {
              term: {
                [IndexFields.LANGUAGE]: urlParams.language.toString(),
              },
            }
          : languageFilter,
        nodeFilter,
      ],
    },
  };

  if (urlParams.language) {
    must.push({
      bool: {
        must: [
          {
            term: {
              [IndexFields.COPIED]: false,
            },
          },
        ],
      },
    });
  }

  if (Object.keys(must).length) {
    query.bool.must = must;
  }

  if (should.length) {
    query.bool.should = should;
    query.bool.minimum_should_match = 1;
  }

  const sort =
    urlParams?.sort === sortOptions.closing
      ? {
          [IndexFields.UNPUBLISH_ON]: {
            order: 'asc',
          },
        }
      : {
          [IndexFields.PUBLICATION_STARTS]: {
            order: 'desc',
          },
        };

  return JSON.stringify({
    aggs: {
      [IndexFields.NUMBER_OF_JOBS]: {
        sum: {
          field: IndexFields.NUMBER_OF_JOBS,
          missing: 1,
        },
      },
    },
    sort: [sort],
    size: size,
    from: size * (page - 1),
    query: query,
  });
};

export default useQueryString;
