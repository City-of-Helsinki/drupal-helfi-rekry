import { atom } from 'jotai';

import { bucketToMap } from './helpers/Aggregations';
import { getLanguageLabel } from './helpers/Language';
import { sortOptions } from './helpers/Options';
import { AGGREGATIONS, EMPLOYMENT_FILTER_OPTIONS, LANGUAGE_OPTIONS, TASK_AREA_OPTIONS } from './query/queries';
import type { AggregationItem } from './types/Aggregations';
import type OptionType from './types/OptionType';
import type Result from './types/Result';
import type Term from './types/Term';
import type URLParams from './types/URLParams';

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
      params[key] = [value];
    }

    result = entries.next();
  }

  return params;
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
      value.forEach((option: string) => newParams.append(key, option));
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
  const ndjsonHeader = '{}';

  const body =
    ndjsonHeader +
    '\n' +
    JSON.stringify(AGGREGATIONS) +
    '\n' +
    ndjsonHeader +
    '\n' +
    JSON.stringify(TASK_AREA_OPTIONS) +
    '\n' +
    ndjsonHeader +
    '\n' +
    JSON.stringify(EMPLOYMENT_FILTER_OPTIONS) +
    '\n' +
    ndjsonHeader +
    '\n' +
    JSON.stringify(LANGUAGE_OPTIONS) +
    '\n';
  return fetch(`${url}/_msearch`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-ndjson',
    },
    body: body,
  })
    .then((res) => res.json())
    .then((json) => {
      // Simplify response for later use.
      const [aggs, taskAreas, employmentOptions, languages] = json?.responses;

      return {
        taskAreaOptions: taskAreas?.hits?.hits || [],
        taskAreas: aggs?.aggregations?.occupations?.buckets || [],
        employment: aggs?.aggregations?.employment?.buckets || [],
        employmentOptions: employmentOptions?.hits?.hits || [],
        employmentType: aggs?.aggregations?.employment_type?.buckets || [],
        languages: languages?.aggregations?.languages?.buckets || [],
      };
    });
});

export const taskAreasAtom = atom<OptionType[]>((get) => {
  const aggs = bucketToMap(get(configurationsAtom)?.taskAreas);
  const options = get(configurationsAtom)?.taskAreaOptions;

  return options
    .map((option: Result<Term>) => {
      const count = aggs.get(option._source.tid[0]) || 0;
      const name = option._source.name;

      return {
        count: count,
        label: `${name} (${count})`,
        simpleLabel: name,
        value: option._source.tid[0],
      };
    })
    .sort((a: OptionType, b: OptionType) => sortOptions(a, b));
});
export const taskAreasSelectionAtom = atom<OptionType[]>([] as OptionType[]);

export const employmentAtom = atom<OptionType[]>((get) => {
  const { employment, employmentOptions, employmentType } = get(configurationsAtom);

  const getCount = (tid: string) => {
    const matchedAgg = employment.concat(employmentType).find((aggData: any) => aggData.key === tid);

    return matchedAgg?.doc_count || 0;
  };

  return employmentOptions
    .map((term: Result<Term>) => {
      const count = getCount(term._source.tid[0]);

      return {
        count: count,
        label: `${term._source.name} (${count})`,
        simpleLabel: term._source.name,
        value: term._source.tid[0],
      };
    })
    .sort((a: OptionType, b: OptionType) => sortOptions(a, b));
});
export const employmentSelectionAtom = atom<OptionType[]>([] as OptionType[]);

export const languagesAtom = atom<OptionType[]>((get) => {
  const languages = get(configurationsAtom)?.languages;

  return languages.map(({ key, doc_count }: AggregationItem) => ({
    label: `${getLanguageLabel(key)} (${doc_count})`,
    simpleLabel: key,
    value: key,
  }));
});
export const languageSelectionAtom = atom<OptionType | null>(null);

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
  set(languageSelectionAtom, null);
});
