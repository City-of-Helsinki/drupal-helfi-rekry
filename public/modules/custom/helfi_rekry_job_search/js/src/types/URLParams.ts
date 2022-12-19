import SearchComponents from '../enum/SearchComponents';
import OptionType from './OptionType';

type URLParams = {
  continuous?: boolean;
  employment?: string[];
  internship?: boolean;
  keyword?: string;
  task_areas?: string[];
  page?: string;
  summer_jobs?: boolean;
  youth_summer_jobs?: boolean;
};

export default URLParams;
