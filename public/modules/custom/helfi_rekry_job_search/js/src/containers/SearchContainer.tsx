import { useAtom } from 'jotai';
import { useEffect, useState } from 'react';
import useSWR from 'swr';

import RadioOptions from '../enum/RadioOptions';
import SearchComponents from '../enum/SearchComponents';
import { getInitialValues } from '../helpers/Params';
import UseQuery from '../hooks/UseQuery';
import UseResult from '../hooks/UseResult';
import getRadioFilter from '../query/getRadioFilter';
import { urlAtom } from '../store';
import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

type RadioOption = keyof typeof RadioOptions | null;

const SIZE = 10;

const SearchContainer = () => {
  const [urlParams, setUrlParams] = useAtom(urlAtom);
  // const [page, setPage] = useState<number>(1);
  // const [radioOption, setRadioOption] = useState<RadioOption>(null)
  // const [query, updateQuery] = useState({});

  let query: any = {
    size: SIZE,
  };
  let keywordQuery = {};
  let page: number | undefined;
  const filter = [];

  for (const key in urlParams) {
    switch (key) {
      case SearchComponents.KEYWORD:
        if (urlParams[key] && urlParams[key]?.length) {
          query.query = {
            match_phrase_prefix: {
              title: {
                query: urlParams[key],
              },
            },
          };
        }
        break;
      case SearchComponents.RADIO_OPTIONS:
        // @ts-ignore
        filter.push(getRadioFilter(urlParams[key]));
        break;
      case SearchComponents.RESULTS:
        // @ts-ignore
        page = Number(urlParams[key]);
        break;
      default:
        break;
    }
  }

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
      body: JSON.stringify(query),
    }).then((res) => res.json());
  };

  const { data, error, isValidating } = useSWR(getQueryString(), fetcher);

  return (
    <div>
      <FormContainer />
      <ResultsContainer size={SIZE} {...data} />
    </div>
  );
};

export default SearchContainer;
