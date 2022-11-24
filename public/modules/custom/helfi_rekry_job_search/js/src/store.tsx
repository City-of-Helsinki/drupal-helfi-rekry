import { atom } from 'jotai';
import { selectAtom } from 'jotai/utils';

import SearchComponents from './enum/SearchComponents';

export const urlAtom = atom(Object.fromEntries(new URLSearchParams(window.location.search)), (get, set, value) => {
  set(urlAtom, value);
  const url = get(urlAtom);

  const newUrl = new URL(window.location.toString());
  const newParams = new URLSearchParams();

  Object.keys(url).map((key) => {
    if (url[key]) {
      newParams.set(key, url[key]);
    } else {
      newParams.delete(key);
    }
  });

  newUrl.search = newParams.toString();

  window.history.pushState({}, '', newUrl);
});

export const keywordAtom = atom('');
export const radioAtom = atom<null | string>(null);

// @ts-ignore
export const pageAtom = atom((get) => Number(get(urlAtom)?.page) || 1);
