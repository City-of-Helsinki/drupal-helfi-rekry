import { LoadingSpinner } from 'hds-react';
import { useAtomValue } from 'jotai';
import useSWR from 'swr';

import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import IndexFields from '../enum/IndexFields';
import { FILTER } from '../query/queries';
import { urlAtom } from '../store';
import type URLParams from '../types/URLParams';

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

  if (urlParams?.task_areas?.length) {
    must.push({
      terms: {
        [`${IndexFields.TASK_AREA}.keyword`]: urlParams.task_areas,
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

const ResultsContainer = () => {
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

  const { data, error } = useSWR(JSON.stringify(urlParams), fetcher);

  if (!data && !error) {
    return <LoadingSpinner />;
  }

  // @todo add no results message.
  if (!data?.hits?.hits) {
    return <div>{Drupal.t('No results')}</div>;
  }

  const results = data.hits.hits;
  const total = data.hits.total.value;
  const pages = Math.floor(total / SIZE);
  const addLastPage = total > SIZE && total % SIZE;

  if (error) {
    console.warn('Error loading data');
    return null;
  }

  return (
    <div>
      {results.map((hit: any) => (
        <ResultCard key={hit._id} {...hit._source} />
      ))}
      <Pagination pages={5} totalPages={addLastPage ? pages + 1 : pages} />
    </div>
  );
};

export default ResultsContainer;
