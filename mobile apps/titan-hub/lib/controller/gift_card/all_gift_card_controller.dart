

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/model/gift_card/gift_card_list_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/services/gift_card_api_services.dart';

class AllGiftCardController extends GetxController {
  RxInt selectedIndex = (-1).obs;
  RxString selectedCountry = 'Select Country'.obs;
  RxString selectedCountryCode = ''.obs;
  late ScrollController scrollController;
  final List<CountryElement> countryList = [];

  @override
  void onInit() {
    giftCardListModelProcess();
    scrollController = ScrollController()..addListener(scrollListener);
    super.onInit();
  }

  void scrollListener() {
    if (scrollController.position.pixels ==
        scrollController.position.maxScrollExtent) {
      giftCardListModelMoreProcess();
    }
  }

  @override
  void dispose() {
    scrollController.dispose();
    super.dispose();
  }

  /// ------------------------------------- >>

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late GiftCardListModel _giftCardListModelModel;
  GiftCardListModel get giftCardListModelModel => _giftCardListModelModel;

  final _isMoreLoading = false.obs;
  bool get isMoreLoading => _isMoreLoading.value;

  int page = 1;
  RxBool hasNextPage = true.obs;
  RxList<Datum> historyList = <Datum>[].obs;

  ///* Get GiftCardListModel in process
  Future<GiftCardListModel> giftCardListModelProcess() async {
    historyList.clear();
    hasNextPage.value = true;
    page = 1;

    _isLoading.value = true;
    update();
    await GiftCardApiServices.allGiftCardInfoProcess(
      page.toString(),
      selectedCountryCode.value,
    ).then((value) {
      _giftCardListModelModel = value!;

      for (var element in _giftCardListModelModel.data.countries) {
        countryList.add(
          CountryElement(
            name: element.name,
            mobileCode: element.mobileCode,
            currencyName: element.currencyName,
            currencyCode: element.currencyCode,
            currencySymbol: element.currencySymbol,
            iso2: element.iso2,
          ),
        );
      }
      if (_giftCardListModelModel.data.products.lastPage > 1) {
        hasNextPage.value = true;
      } else {
        hasNextPage.value = false;
      }

      historyList.addAll(_giftCardListModelModel.data.products.data);

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isLoading.value = false;
    update();
    return _giftCardListModelModel;
  }

  ///* Get GiftCardListModel in process
  Future<GiftCardListModel> giftCardListModelMoreProcess() async {
    page++;

    if (hasNextPage.value && !_isMoreLoading.value) {
      _isMoreLoading.value = true;
      update();
      await GiftCardApiServices.allGiftCardInfoProcess(
        page.toString(),
        selectedCountryCode.value,
      ).then((value) {
        _giftCardListModelModel = value!;

        var data = _giftCardListModelModel.data.products.lastPage;
        historyList.addAll(_giftCardListModelModel.data.products.data);
        if (page >= data) {
          hasNextPage.value = false;
        }
        _isMoreLoading.value = false;
        update();
      }).catchError((onError) {
        log.e(onError);
      });
      _isMoreLoading.value = false;
      update();
    }
    return _giftCardListModelModel;
  }
}
