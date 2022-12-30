import { LoadingSpinner } from 'hds-react';
import { useAtomValue } from 'jotai';
import { Fragment } from 'react';
import useSWR from 'swr';

import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import ResultsSort from '../components/results/ResultsSort';
import Global from '../enum/Global';
import IndexFields from '../enum/IndexFields';
import useQueryString from '../hooks/useQueryString';
import { urlAtom } from '../store';
import type URLParams from '../types/URLParams';

const ResultsContainer = () => {
  const { size } = Global;
  const urlParams: URLParams = useAtomValue(urlAtom);
  const queryString = useQueryString(urlParams);
  const fetcher = () => {
    const proxyUrl = drupalSettings?.helfi_rekry_job_search?.elastic_proxy_url;
    const url: string | undefined = proxyUrl || process.env.REACT_APP_ELASTIC_URL;

    return fetch(`${url}/_search`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: queryString,
    }).then((res) => res.json());
  };

  const { data, error } = useSWR(queryString, fetcher);

  if (!data && !error) {
    return <LoadingSpinner />;
  }

  if (!data?.hits?.hits.length) {
    return (
      <div className='job-search__no-results'>
        <div className='job-search__no-results__heading'>{Drupal.t('No results')}</div>
        <div>{Drupal.t('No results match the given parameters. Remove some of the filter selections.')}</div>
      </div>
    );
  }

  const results = data.hits.hits;
  const total = data.hits.total.value;
  const pages = Math.floor(total / size);
  const addLastPage = total > size && total % size;

  if (error) {
    console.warn('Error loading data');
    return null;
  }

  // Total number of available jobs
  const jobs: number = data?.aggregations?.[IndexFields.NUMBER_OF_JOBS]?.value;

  return (
    <div className='job-search__results'>
      <div className='job-search__results-stats'>
        <div className='job-listing-search__count-container'>
          {!isNaN(jobs) && !isNaN(total) && (
            <Fragment>
              <span className='job-listing-search__count'>{jobs}</span>
              {' ' + Drupal.t('open positions', { '@listings': total }, { context: 'Job search results statline' })}
            </Fragment>
          )}
        </div>
        <ResultsSort />
      </div>
      {results.map((hit: any) => (
        <ResultCard key={hit._id} {...hit._source} />
      ))}
      <Pagination pages={5} totalPages={addLastPage ? pages + 1 : pages} />
    </div>
  );
};

export default ResultsContainer;
