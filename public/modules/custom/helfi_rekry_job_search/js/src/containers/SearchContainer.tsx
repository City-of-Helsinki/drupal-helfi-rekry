import { getInitialValues } from '../helpers/Params';
import ResultsContainer from './ResultsContainer';

const SearchContainer = () => {
  const initialValues = getInitialValues();

  return <ResultsContainer initialValues={initialValues} />;
};

export default SearchContainer;
