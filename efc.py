
# coding: utf-8

# In[65]:

import pandas as pd 


# In[66]:

df = pd.read_csv("train_ridership_one_month.csv")
df["datetime"] = df["transit_day"] + " " + df["transit_time"]
df["datetime"] = pd.to_datetime(df["datetime"], infer_datetime_format=True)
df.tail()


# In[82]:

# 1, 9, 12 entry

# Below code removes unwanted passenger activity codes for parking
print df.use_type.unique()
valid_codes = [1, 9, 10, 11, 12]
df = df[df.use_type != 13 ]
df = df[df.use_type != 14 ]
clean_df = df[df.use_type != 15 ]
cols = ["datetime", "station_id", "use_type", "serial_number"]
clean_df = clean_df[cols]


# In[99]:

entry_values = [1, 9, 12]
exit_values = [10, 11]

entry_df = clean_df.loc[df['use_type'].isin(entry_values)]
entry_df.rename(columns = {'datetime':'entry_time'}, inplace = True)
exit_df = clean_df.loc[df['use_type'].isin(exit_values)]
exit_df.rename(columns = {'datetime':'exit_time'}, inplace = True)
entry_df.head()


# In[100]:

exit_df.head()


# In[102]:

entry_df = entry_df.sort_values(["entry_time"], ascending=True)
entry_df.head()


# In[103]:

exit_df = exit_df.sort_values(["exit_time"], ascending=True)
exit_df.head()


# In[122]:

def min_time_difference(entry_time, serial_number):
    #find the rows corresponding to the same id
    same_person_df = exit_df[exit_df["serial_number"] == serial_number]
    same_person_df["time_diff"] = same_person_df["exit_time"].map(lambda x: x - entry_time)
    min_diff = same_person_df["time_diff"].min()
    return same_person_df[same_person_df["time_diff"] == min_diff]
    
    


# In[ ]:

marta_dict = dict(
                    entry_type = "",
                    exit_type = "",
                    entry_time = "",
                    exit_time = "",
                    entry_station = "",
                    exit_station = "",
                    trav_time = "",
                    serial_num = "")


merged_df = pd.DataFrame(data=marta_dict, index=[0])

for index, val in entry_df.iterrows():
#     if val["serial_number"] == "3E8CAE0248C14E4AE050":
    match = min_time_difference(val["entry_time"], val["serial_number"])
    marta_dict = dict(
                    entry_type = val["use_type"],
                    exit_type = match["use_type"],
                    entry_time = val["entry_time"],
                    exit_time = match["exit_time"],
                    entry_station = val["station_id"],
                    exit_station = match["station_id"],
                    trav_time = match["time_diff"],
                    serial_num = val["serial_number"])
    df_toappend = pd.DataFrame.from_dict(marta_dict)
    merged_df = merged_df.append(df_toappend, ignore_index=False, verify_integrity=False)


merged_df.head()
print merged_df.counts()
merged_df.to_csv('efc.csv')

    


# In[ ]:




