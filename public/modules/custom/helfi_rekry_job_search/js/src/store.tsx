import { atom } from 'jotai';

import { AGGREGATIONS } from './query/queries';
import type OptionType from './types/OptionType';
import type URLParams from './types/URLParams';

// import SearchComponents from './enum/SearchComponents';

type InitialParams = { [key: string]: string | string[] };

const transformParams = (initialParams: InitialParams) => {
  let urlParams: URLParams = { ...initialParams };

  if (initialParams?.task_areas) {
    const taskAreas = initialParams.task_areas;
    const isArray = Array.isArray(taskAreas);

    urlParams.task_areas = isArray
      ? taskAreas.map((value: string) => ({ label: value, value: value }))
      : [{ label: taskAreas, value: taskAreas }];
  }

  return urlParams;
};

const getParams = (searchParams: URLSearchParams) => {
  let params: { [k: string]: any } = {};
  const entries = searchParams.entries();
  let result = entries.next();

  while (!result.done) {
    const [key, value] = result.value;

    if (!value) {
      result = entries.next();
      continue;
    }

    const existing = params[key];
    if (existing) {
      const updatedValue = Array.isArray(existing) ? [...existing, value] : [existing, value];
      params[key] = updatedValue;
    } else {
      params[key] = value;
    }

    result = entries.next();
  }

  return transformParams(params);
};

export const urlAtom = atom<URLParams>(getParams(new URLSearchParams(window.location.search)));

export const urlUpdateAtom = atom(null, (get, set, values: URLParams) => {
  //set atom value
  values.page = values.page || '1';
  set(urlAtom, values);

  // Set new params to window.location
  const newUrl = new URL(window.location.toString());
  const newParams = new URLSearchParams();
  // eslint-disable-next-line array-callback-return
  for (const key in values) {
    const value = values[key as keyof URLParams];

    if (Array.isArray(value)) {
      value.forEach((option: OptionType) => newParams.append(key, option.value));
    } else if (value) {
      newParams.set(key, value.toString());
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

export const configurationsAtom = atom(async () => {
  const proxyUrl = drupalSettings?.helfi_rekry_job_search?.elastic_proxy_url;
  const url: string | undefined = proxyUrl || process.env.REACT_APP_ELASTIC_URL;

  return fetch(`${url}/_search`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(AGGREGATIONS),
  })
    .then((res) => res.json())
    .then((json) => json.aggregations);
});

// TODO fetch data from elastic
export const taskAreasAtom = atom<OptionType[]>((get) => {
  const conf = get(configurationsAtom);
  return conf.occupations.buckets.map(({ key, doc_count }: { key: string; doc_count: number }) => {
    return { label: `${key} (${doc_count})`, value: key.trim() as string };
  }) as OptionType[];
});
//TODO connect these two
export const taskAreasSelectionAtom = atom<OptionType[]>([] as OptionType[]);
export const continuousAtom = atom<boolean>(false);
export const internshipAtom = atom<boolean>(false);
export const summerJobsAtom = atom<boolean>(false);
export const youthSummerJobsAtom = atom<boolean>(false);

export const resetFormAtom = atom(null, (get, set) => {
  set(taskAreasSelectionAtom, []);
  set(keywordAtom, '');
  set(continuousAtom, false);
  set(internshipAtom, false);
  set(summerJobsAtom, false);
  set(youthSummerJobsAtom, false);
  set(urlUpdateAtom, {});
});
