import { atom } from 'jotai';

import type OptionType from './types/OptionType';
import type URLParams from './types/URLParams';

// import SearchComponents from './enum/SearchComponents';

export const urlAtom = atom(Object.fromEntries(new URLSearchParams(window.location.search)));

export const urlUpdateAtom = atom(null, (get, set, values: URLParams) => {
  //set atom value
  const oldValues = get(urlAtom);
  if (values.page && oldValues.keyword !== values.keyword) {
    values.page = '1';
  }
  set(urlAtom, values);

  // Set new params to window.location
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

export const setPageAtom = atom(null, (get, set, page: string) => {
  const url = get(urlAtom);
  set(urlUpdateAtom, { ...url, page });
});

export const pageAtom = atom((get) => Number(get(urlAtom)?.page) || 1);

// TODO fetch data from elastic
export const occupationsAtom = atom<OptionType[] | Promise<OptionType[]>>(async () => [
  { label: 'Palomies', value: '1' },
  { label: 'Esihenkilö', value: '2' },
  { label: 'Kadunlakaisija', value: '3' },
]);
//TODO connect these two
export const occupationSelectionAtom = atom<OptionType | null>(null);

// Checkbox atoms
export const continuousAtom = atom<boolean>(false);
export const internshipAtom = atom<boolean>(false);
export const summerJobsAtom = atom<boolean>(false);
export const youthSummerJobsAtom = atom<boolean>(false);
