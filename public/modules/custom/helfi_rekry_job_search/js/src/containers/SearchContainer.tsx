import { LoadingSpinner } from 'hds-react';
import { useAtomValue } from 'jotai';
import { Suspense } from 'react';
import useSWR from 'swr';

import IndexFields from '../enum/IndexFields';
import { FILTER } from '../query/queries';
import { urlAtom } from '../store';
import OptionType from '../types/OptionType';
import type URLParams from '../types/URLParams';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

const SIZE = 10;

const getQueryParamString = (urlParams: URLParams): string => {
  const page = Number.isNaN(Number(urlParams.page)) ? 1 : Number(urlParams.page);
  const must = [];
  const should = [];

  if (urlParams.keyword && urlParams.keyword.length > 0) {
    must.push({
      bool: {
        should: [
          {
            combined_fields: {
              query: urlParams.keyword,
              fields: [`${IndexFields.TITLE}^2`, IndexFields.EMPLOYMENT],
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

  if (urlParams?.occupations?.length) {
    must.push({
      terms: {
        [`${IndexFields.TASK_AREA}.keyword`]: urlParams.occupations.map((occupation: OptionType) => occupation.value),
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

  if (urlParams.summerJobs) {
    should.push({
      term: {
        [IndexFields.SUMMER_JOB]: true,
      },
    });
  }

  if (urlParams.youthSummerJobs) {
    should.push({
      term: {
        [IndexFields.YOUTH_SUMMER_JOB]: true,
      },
    });
  }

  const query: any = {
    bool: {
      ...FILTER,
    },
  };

  if (Object.keys(must).length) {
    query.bool.must = must;
  }

  if (should.length) {
    query.bool.should = should;
    query.bool.minimum_should_match = 1;
  }

  return JSON.stringify({
    size: SIZE,
    from: SIZE * (page - 1),
    query: query,
  });
};

const SearchContainer = () => {
  const urlParams: URLParams = useAtomValue(urlAtom);
  const fetcher = () => {
    const proxyUrl = drupalSettings?.helfi_rekry_job_search?.elastic_proxy_url;
    const url: string | undefined = proxyUrl || process.env.REACT_APP_ELASTIC_URL;

    return fetch(`${url}/_search`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: getQueryParamString(urlParams),
    }).then((res) => res.json());
  };

  const { data, error, isValidating } = useSWR(JSON.stringify(urlParams), fetcher);

  return (
    <div className='recruitment-search'>
      {/* For async atoms that need to load option values from elastic*/}
      <Suspense fallback={<LoadingSpinner />}>
        <FormContainer />
      </Suspense>
      {!data && !error && <LoadingSpinner />}
      {data && error && 'Error'}
      {data && !error && !isValidating && <ResultsContainer size={SIZE} {...data} />}
    </div>
  );
};

export default SearchContainer;
