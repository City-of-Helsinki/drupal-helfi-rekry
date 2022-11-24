import { useEffect, useState } from 'react';

import getRadioFilter from '../query/getRadioFilter';

type UseQueryParams = {
  radioOption?: string;
  page: number;
  size: number;
};

const getQuery = (params: UseQueryParams) => {
  const { radioOption, page, size } = params;
  let query: any = {};
  let filter: any = [];

  if (radioOption) {
    filter.push(getRadioFilter(radioOption));
  }

  if (filter.length) {
    query.bool = {
      filter: filter,
    };
  } else {
    query.match_all = {};
  }

  return {
    query: query,
    from: size * (page - 1),
    size: size,
  };
};

const UseQuery = (initial: UseQueryParams) => {
  const [query, setQuery] = useState<any>(getQuery(initial));
  const updateQuery = (params: UseQueryParams) => {
    setQuery(getQuery(params));
  };

  return [query, updateQuery];
};

export default UseQuery;
