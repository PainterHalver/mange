import React from 'react';
import { Col, Row, Tabs } from 'antd';
import TopManga from '../home/TopManga';
import { useContext, useState } from 'react';
import { MangaContext } from '../../../providers/mangaProvider/index';
import Manga from '../../../components/manga/Manga';
import { proposes } from '../home/index';

const History = () => {
  const { histories, historiesAccount, topMangaWeek, topMangaMonth } =
    useContext(MangaContext);

  const [key, setKey] = useState('1');

  const items = [
    {
      key: '1',
      label: <div className="text_title">Từ thiết bị</div>,
    },
    {
      key: '2',
      label: <div className="text_title">Từ tài khoản</div>,
    },
  ];

  const onChange = (key) => {
    setKey(key);
  };

  return (
    <Row style={{ justifyContent: 'center' }}>
      <Col span={18}>
        <Row>
          <Col
            span={16}
            className="box-content"
            style={{ borderRight: '1px solid black' }}
          >
            <Row>
              <Tabs
                defaultActiveKey="1"
                items={items}
                onChange={onChange}
                indicatorSize={(origin) => origin - 16}
              />
            </Row>
            <Row gutter={[16, 24]}>
              {key === '1'
                ? histories.map((item) => {
                    console.log(item);
                    return <Manga manga={item} />;
                  })
                : historiesAccount.map((item) => {
                    return <Manga manga={item} />;
                  })}
            </Row>
          </Col>
          <Col span={8}>
            <TopManga mangaWeek={topMangaWeek} mangaMonth={topMangaMonth} />
          </Col>
        </Row>
      </Col>
    </Row>
  );
};

export default History;
