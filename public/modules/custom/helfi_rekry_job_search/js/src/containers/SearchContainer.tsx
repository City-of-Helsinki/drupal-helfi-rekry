import { useAtomValue } from 'jotai';
import useSWR from 'swr';

import getRadioFilter from '../query/getRadioFilter';
import { urlAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

export type URLParams = {
  keyword?: string;
  page?: string;
  continuous?: string;
};

const SIZE = 10;

const getQueryParamString = (urlParams: URLParams) => {
  const filter = [];
  const page = Number.isNaN(Number(urlParams.page)) ? 1 : Number(urlParams.page);
  let query: any = {
    size: SIZE,
    from: SIZE * (page - 1),
    query: {
      match_all: {},
    },
  };

  if (urlParams.keyword && urlParams.keyword.length > 0) {
    query.query = {
      match_phrase_prefix: {
        title: {
          query: urlParams.keyword,
        },
      },
    };
  }

  if (urlParams.continuous) {
    filter.push(getRadioFilter(urlParams.continuous));
  }

  return JSON.stringify(query);
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
    <div>
      <FormContainer />
      {!data && !error && 'loading'}
      {data && error && 'Error'}
      {data && !error && !isValidating && <ResultsContainer size={SIZE} {...data} />}
    </div>
  );
};

export default SearchContainer;
