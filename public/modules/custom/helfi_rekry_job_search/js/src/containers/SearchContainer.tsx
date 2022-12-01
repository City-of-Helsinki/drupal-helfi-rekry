import { useAtomValue } from 'jotai';
import useSWR from 'swr';

import SearchComponents from '../enum/SearchComponents';
import getRadioFilter from '../query/getRadioFilter';
import { urlAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

type URLParams = {
  [k: string]: string;
};

const SIZE = 10;

const getQueryParamString = (urlParams: URLParams) => {
  //TODO Elastic query object type?
  let query: any = {
    size: SIZE,
    query: undefined,
  };

  let page: number | undefined;
  const filter = [];

  for (const key in urlParams) {
    switch (key) {
      case SearchComponents.KEYWORD:
        if (urlParams.keyword && urlParams.keyword?.length) {
          query.query = {
            match_phrase_prefix: {
              title: {
                query: urlParams.keyword,
              },
            },
          };
        }
        break;
      case SearchComponents.RADIO_OPTIONS:
        // @ts-ignore
        filter.push(getRadioFilter(urlParams.continuous));
        break;
      case SearchComponents.RESULTS:
        // @ts-ignore
        page = Number(urlParams.page);
        break;
      default:
        break;
    }
  }
  //overwrite?
  if (filter.length) {
    query.query = {
      bool: {
        filter: filter,
      },
    };
  }

  if (page) {
    query.from = SIZE * (page - 1);
  } else {
    query.query = {
      match_all: {},
    };
  }

  return JSON.stringify(query);
};

const SearchContainer = () => {
  const urlParams: URLParams = useAtomValue(urlAtom);

  const getQueryString = () => {
    return JSON.stringify(urlParams);
  };

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

  const { data, error, isValidating } = useSWR(getQueryString(), fetcher);

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
