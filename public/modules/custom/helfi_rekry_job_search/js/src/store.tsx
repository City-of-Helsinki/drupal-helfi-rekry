import { atom } from 'jotai';

import { URLParams } from './containers/SearchContainer';

// import SearchComponents from './enum/SearchComponents';

// export type URLParams = {
//   // [k: string]: string
//   continuous: string | null
//   keyword: string | null
//   page: string | null
// }

export const urlAtom = atom(Object.fromEntries(new URLSearchParams(window.location.search)));

export const urlUpdateAtom = atom(null, (get, set, values: URLParams) => {
  //set atom value

  const oldValues = get(urlAtom);
  if (values.page && oldValues.keyword !== values.keyword) {
    values.page = '1';
  }
  set(urlAtom, values);

  // Set new params to window.location
  // TODO Maybe do a separate writeAtom for this side-effect, for clarity's sake
  const url = get(urlAtom);
  const newUrl = new URL(window.location.toString());
  const newParams = new URLSearchParams();
  // eslint-disable-next-line array-callback-return
  for (const key in url) {
    if (url[key]) {
      newParams.set(key, url[key]);
    } else {
      newParams.delete(key);
    }
  }

  newUrl.search = newParams.toString();
  window.history.pushState({}, '', newUrl);
});

export const keywordAtom = atom('');
export const radioAtom = atom<null | string>(null);

export const setPageAtom = atom(null, (get, set, page: string) => {
  const url = get(urlAtom);
  set(urlUpdateAtom, { ...url, page });
});

// @ts-ignore
export const pageAtom = atom((get) => Number(get(urlAtom)?.page) || 1);
