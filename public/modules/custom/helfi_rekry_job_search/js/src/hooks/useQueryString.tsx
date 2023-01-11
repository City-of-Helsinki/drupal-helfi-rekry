import Global from '../enum/Global';
import IndexFields, { CustomLabels } from '../enum/IndexFields';
import { languageFilter, nodeFilter, publicationQuery } from '../query/queries';
import URLParams from '../types/URLParams';

/**
 * Dirty fix for combining results for multiple tids.
 * Combine results for public service / contractual employments.
 * (Virkasuhde / työsuhde)
 */
const combineEmploymentTypes = (types: (number | string)[]) => {
  if (types.includes(89) && !types.includes(88)) {
    types.push(88);
  }
  if (types.includes(91) && !types.includes(90)) {
    types.push(90);
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
            match_phrase_prefix: {
              [IndexFields.RECRUITMENT_ID]: urlParams.keyword.toString(),
            },
          },
          {
            combined_fields: {
              query: urlParams.keyword.toString(),
              fields: [`${IndexFields.TITLE}^2`, IndexFields.EMPLOYMENT, IndexFields.ORGANIZATION_NAME],
            },
          },
          {
            wildcard: {
              [`${IndexFields.TITLE}.keyword`]: `*${urlParams.keyword.toString()}*`,
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
        [IndexFields.CUSTOM_LABELS]: CustomLabels.is_continuous,
      },
    });
  }

  if (urlParams.internship) {
    should.push({
      term: {
        [IndexFields.CUSTOM_LABELS]: CustomLabels.is_internship,
      },
    });
  }

  if (urlParams.summer_jobs) {
    should.push({
      term: {
        [IndexFields.CUSTOM_LABELS]: CustomLabels.is_summer_job,
      },
    });
  }

  if (urlParams.youth_summer_jobs) {
    should.push({
      term: {
        [IndexFields.CUSTOM_LABELS]: CustomLabels.is_youth_summer_job,
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
