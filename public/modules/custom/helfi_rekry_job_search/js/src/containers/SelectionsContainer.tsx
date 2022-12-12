import { Button, IconCross } from 'hds-react';
import { SetStateAction, WritableAtom, useAtom, useAtomValue } from 'jotai';
import { useUpdateAtom } from 'jotai/utils';
import { Fragment, MouseEventHandler } from 'react';

import SearchComponents from '../enum/SearchComponents';
import {
  continuousAtom,
  internshipAtom,
  resetFormAtom,
  summerJobsAtom,
  taskAreasSelectionAtom,
  urlAtom,
  urlUpdateAtom,
  youthSummerJobsAtom,
} from '../store';
import { CONTINUOUS, INTERNSHIPS, SUMMER_JOBS, YOUTH_SUMMER_JOBS } from '../translations';
import OptionType from '../types/OptionType';

const SelectionsContainer = () => {
  const urlParams = useAtomValue(urlAtom);
  const resetForm = useUpdateAtom(resetFormAtom);

  const showClearButton =
    urlParams?.keyword?.length ||
    urlParams?.task_areas?.length ||
    urlParams?.continuous ||
    urlParams?.internship ||
    urlParams?.summer_jobs ||
    urlParams?.youth_summer_jobs;

  const showTaskAreas = Boolean(urlParams.task_areas?.length && urlParams.task_areas.length > 0);

  return (
    <div className='job-search-form__selections-wrapper'>
      <ul className='job-search-form__selections-container content-tags__tags'>
        {showTaskAreas && <ListFilter atom={taskAreasSelectionAtom} valueKey={SearchComponents.TASK_AREAS} />}
        {urlParams.continuous && (
          <CheckboxFilterPill label={CONTINUOUS.value} atom={continuousAtom} valueKey={SearchComponents.CONTINUOUS} />
        )}
        {urlParams.internship && (
          <CheckboxFilterPill label={INTERNSHIPS.value} atom={internshipAtom} valueKey={SearchComponents.INTERNSHIPS} />
        )}
        {urlParams.summer_jobs && (
          <CheckboxFilterPill label={SUMMER_JOBS.value} atom={summerJobsAtom} valueKey={SearchComponents.SUMMER_JOBS} />
        )}
        {urlParams.youth_summer_jobs && (
          <CheckboxFilterPill
            label={YOUTH_SUMMER_JOBS.value}
            atom={youthSummerJobsAtom}
            valueKey={SearchComponents.YOUTH_SUMMER_JOBS}
          />
        )}
        <li className='job-search-form__clear-all'>
          <Button
            aria-hidden={showClearButton ? 'true' : 'false'}
            className='job-search-form__clear-all-button'
            iconLeft={<IconCross className='job-search-form__clear-all-icon' />}
            onClick={resetForm}
            style={showClearButton ? {} : { visibility: 'hidden' }}
            variant='supplementary'
          >
            {Drupal.t('Clear selections', {}, { context: 'News archive clear selections' })}
          </Button>
        </li>
      </ul>
    </div>
  );
};

export default SelectionsContainer;

type ListFilterProps = {
  atom: typeof taskAreasSelectionAtom;
  valueKey: string;
};

const ListFilter = ({ atom, valueKey }: ListFilterProps) => {
  const [selections, setValue] = useAtom(atom);
  const urlParams = useAtomValue(urlAtom);
  const setUrlParams = useUpdateAtom(urlUpdateAtom);

  const removeSelection = (value: string) => {
    const newValue = selections;
    const index = newValue.findIndex((selection: OptionType) => selection.value === value);
    newValue.splice(index, 1);
    setValue(newValue);
    setUrlParams({
      ...urlParams,
      [valueKey]: newValue,
    });
  };

  return (
    <Fragment>
      {selections.map((selection: OptionType) => (
        <FilterButton
          value={selection.value}
          clearSelection={() => removeSelection(selection.value)}
          key={selection.value}
        />
      ))}
    </Fragment>
  );
};

type CheckboxFilterPillProps = {
  atom: WritableAtom<boolean, SetStateAction<boolean>, void>;
  valueKey: string;
  label: string;
};

const CheckboxFilterPill = ({ atom, valueKey, label }: CheckboxFilterPillProps) => {
  const setValue = useUpdateAtom(atom);
  const urlParams = useAtomValue(urlAtom);
  const setUrlParams = useUpdateAtom(urlUpdateAtom);

  return (
    <FilterButton
      value={label}
      clearSelection={() => {
        setUrlParams({ ...urlParams, [valueKey]: false });
        setValue(false);
      }}
    />
  );
};

type FilterButtonProps = {
  value: string;
  clearSelection: MouseEventHandler<HTMLLIElement>;
};

const FilterButton = ({ value, clearSelection }: FilterButtonProps) => {
  return (
    <li
      className='content-tags__tags__tag content-tags__tags--interactive'
      key={'test' + value.toString()}
      onClick={clearSelection}
    >
      <Button
        aria-label={Drupal.t(
          'Remove @item from search results',
          { '@item': value.toString() },
          { context: 'Search: remove item aria label' }
        )}
        className='job-search-form__remove-selection-button'
        iconRight={<IconCross />}
        variant='supplementary'
      >
        {value}
      </Button>
    </li>
  );
};
