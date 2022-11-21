import { MutableRefObject, useRef } from 'react';

import InitialState from '../types/InitialState';

type UpdateOptions = {
  page?: number;
};

const paramsToState = (params: MutableRefObject<URLSearchParams>) => {
  let initialParams: InitialState = {};

  const intialPage = params.current.get('page');
  if (intialPage) {
    initialParams.page = Number(intialPage);
  }

  return initialParams;
};

const useSearchParams = () => {
  let urlSearchParams = useRef<URLSearchParams>(new URLSearchParams(window.location.search));
  const initialParams = paramsToState(urlSearchParams);

  const updateUrl = (urlSearchParams: MutableRefObject<URLSearchParams>) => {
    let allParamsString = '';
    const entries = urlSearchParams.current.entries();
    let result = entries.next();

    while (!result.done) {
      const [key, value] = result.value;
      const parsedValue = JSON.parse(value);
      let paramString = '';

      if (key === 'page') {
        paramString = `${key}=${value}`;
      } else {
        for (let i = 0; i < parsedValue.length; i++) {
          if (paramString.length) {
            paramString += '&';
          }

          paramString += `${key}[${i}]=${parsedValue[i].replaceAll(' ', '+').toLowerCase()}`;
        }
      }

      allParamsString += allParamsString.length ? '&' + paramString : paramString;
      result = entries.next();
    }

    if (allParamsString.length) {
      allParamsString = '?' + allParamsString;
    }

    const newUrl = new URL(window.location.pathname, window.location.origin);
    newUrl.search = allParamsString;
    window.history.pushState({}, '', newUrl.toString());
  };

  const updateParams = (options: UpdateOptions) => {
    urlSearchParams.current = new URLSearchParams();
    for (const key in options) {
      const selections = options[key as keyof UpdateOptions];

      if (key === 'page' && !!selections) {
        urlSearchParams.current.set(key, selections.toString());
      } else if (Array.isArray(selections) && selections?.length) {
        const values = selections.map((selection: any) => selection);
        urlSearchParams.current.set(key, JSON.stringify(values));
      }
    }

    updateUrl(urlSearchParams);
  };

  return [initialParams, updateParams] as const;
};

export default useSearchParams;
