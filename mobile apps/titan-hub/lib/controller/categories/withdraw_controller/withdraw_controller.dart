import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/categories/withdraw/flutterwave_account_cheack_model.dart';
import 'package:qrpay/backend/utils/custom_snackbar.dart';

import '../../../backend/model/categories/withdraw/bank_account_check_model.dart';
import '../../../backend/model/categories/withdraw/flutter_wave_banks_branch_model.dart';
import '../../../backend/model/categories/withdraw/flutter_wave_banks_model.dart';
import '../../../backend/model/categories/withdraw/money_out_manual_insert_model.dart';
import '../../../backend/model/categories/withdraw/money_out_payment_getway_model.dart';
import '../../../backend/model/categories/withdraw/withdraw_flutterwave_insert_model.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_endpoint.dart';
import '../../../backend/services/api_services.dart';
import '../../../backend/utils/logger.dart';
import '../../../backend/utils/request_process.dart';
import '../../../language/english.dart';
import '../../../language/language_controller.dart';
import '../../../model/id_type_model.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../widgets/inputs/manual_payment_image_widget_for_money_out.dart';
import '../../../widgets/inputs/primary_input_filed.dart';
import '../../../widgets/payment_link/custom_drop_down.dart';
import '../../../widgets/text_labels/custom_title_heading_widget.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

final log = logger(WithdrawController);

class WithdrawController extends GetxController {
  final amountTextController = TextEditingController();
  final beneficiaryNameController = TextEditingController();
  List<String> totalAmount = [];

  // Flutter Wave
  final branchNameController = TextEditingController();
  List<TextEditingController> inputFieldControllersFlutterWave = [];
  RxList inputFieldsFlutterWave = [].obs;
  List<String> listImagePathFlutterWave = [];
  List<String> listFieldNameFlutterWave = [];
  RxBool hasFileFlutterWave = false.obs;

  RxString selectCurrency = 'USD'.obs;
  RxString selectWallet = 'Paypal'.obs;
  RxString currencyWalletCode = "".obs;

  List<TextEditingController> inputFieldControllers = [];
  RxList inputFields = [].obs;
  List<String> listImagePath = [];
  List<String> listFieldName = [];
  RxBool hasFile = false.obs;
  RxBool isBranch = false.obs;

  final selectedIDType = "".obs;
  List<IdTypeModel> idTypeList = [];

  RxString baseCurrency = "".obs;
  RxString selectedCurrencyAlias = "".obs;
  RxString selectedCurrencyName = "Select Method".obs;
  RxString selectedCurrencyType = "".obs;
  RxString selectedGatewaySlug = "".obs;
  RxString currencyCode = "".obs;
  RxInt selectedCurrencyId = 0.obs;
  RxInt crypto = 0.obs;
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;

  String enteredAmount = "";
  String transferFeeAmount = "";
  String totalCharge = "";
  String youWillGet = "";
  String payableAmount = "";

  List<Currency> currencyList = [];
  List<String> baseCurrencyList = [];

  /// >>> Flutter
  RxString selectFlutterWaveBankName = "".obs;
  RxString selectFlutterWaveBankCode = "".obs;
  RxInt selectFlutterWaveBankId = 0.obs;
  RxString gatewayTrx = "".obs;
  final remainingController = Get.put(RemaingBalanceController());

  // branch
  RxString selectFlutterWaveBankBranchName = "".obs;
  RxString selectFlutterWaveBankBranchCode = "".obs;

  List<BankInfos> bankInfoList = [];
  List<BankBranch> bankBranchInfoList = [];

  @override
  void dispose() {
    amountTextController.dispose();

    super.dispose();
  }

  @override
  void onInit() {
    amountTextController.text = '0.0';
    getWithdrawInfoData();
    super.onInit();
  }

  // ---------------------------- AddMoneyPaymentGatewayModel ------------------
  // api loading process indicator variable
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late WithdrawInfoModel _moneyOutPaymentGatewayModel;

  WithdrawInfoModel get moneyOutPaymentGatewayModel =>
      _moneyOutPaymentGatewayModel;

  // --------------------------- Api function ----------------------------------
  // get moneyOutPaymentGateway data function
  Future<WithdrawInfoModel> getWithdrawInfoData() async {
    _isLoading.value = true;
    update();

    await ApiServices.withdrawInfoAPi().then((value) {
      _moneyOutPaymentGatewayModel = value!;

      currencyCode.value =
          _moneyOutPaymentGatewayModel.data.userWallet.currency;
      currencyWalletCode.value = _moneyOutPaymentGatewayModel
          .data.gateways.first.currencies.first.currencyCode;

      for (var gateways in _moneyOutPaymentGatewayModel.data.gateways) {
        for (var currency in gateways.currencies) {
          currencyList.add(
            Currency(
              id: currency.id,
              paymentGatewayId: currency.paymentGatewayId,
              crypto: currency.crypto,
              name: currency.name,
              alias: currency.alias,
              currencyCode: currency.currencyCode,
              currencySymbol: currency.currencySymbol,
              minLimit: currency.minLimit,
              maxLimit: currency.maxLimit,
              percentCharge: currency.percentCharge,
              fixedCharge: currency.fixedCharge,
              dailyLimit: currency.dailyLimit,
              monthlyLimit: currency.monthlyLimit,
              rate: currency.rate,
              createdAt: currency.createdAt,
              updatedAt: currency.updatedAt,
              type: currency.type,
              image: currency.image,
            ),
          );
        }
      }

      Currency currency =
          _moneyOutPaymentGatewayModel.data.gateways.first.currencies.first;
      Gateway gateway = _moneyOutPaymentGatewayModel.data.gateways.first;

      selectedCurrencyAlias.value = currency.alias;
      selectedCurrencyType.value = currency.type;
      selectedGatewaySlug.value = gateway.slug;
      selectedCurrencyId.value = currency.id;
      selectedCurrencyName.value = currency.name;
      crypto.value = currency.crypto;
      // currencyCode.value = currency.currencyCode;

      rate.value = double.parse(currency.rate);

      fee.value = double.parse(currency.fixedCharge);
      limitMin.value = double.parse(currency.minLimit) / rate.value;
      limitMax.value = double.parse(currency.maxLimit) / rate.value;
      dailyLimit.value = double.parse(currency.dailyLimit) / rate.value;
      monthlyLimit.value = double.parse(currency.monthlyLimit) / rate.value;
      percentCharge.value = double.parse(currency.percentCharge);

      remainingController.transactionType.value =
          _moneyOutPaymentGatewayModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _moneyOutPaymentGatewayModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _moneyOutPaymentGatewayModel.data.gateways.first.currencies.first.id;
      remainingController.senderAmount.value = amountTextController.text;
      remainingController.senderCurrency.value =
          _moneyOutPaymentGatewayModel.data.userWallet.currency;

      remainingController.getRemainingBalanceProcess();
      //Base Currency
      baseCurrency.value = _moneyOutPaymentGatewayModel.data.baseCurr;
      baseCurrencyList.add(baseCurrency.value);

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _moneyOutPaymentGatewayModel;
  }

  // ---------------------------- Get Flutterwave banks ------------------
  late FlutterWaveBanksModel _flutterWaveBanksModel;

  FlutterWaveBanksModel get flutterWaveBanksModel => _flutterWaveBanksModel;

  // --------------------------- Api function ----------------------------------
  // get flutter Wave Banks data function
  Future<FlutterWaveBanksModel> getFlutterWaveBanks() async {
    _isLoading.value = true;
    update();

    await ApiServices.getFlutterWaveBanksApi(
      withdrawFlutterwaveInsertModel.data.paymentInformations.trx,
    ).then((value) {
      _flutterWaveBanksModel = value!;
      for (var element in _flutterWaveBanksModel.data.bankInfo) {
        bankInfoList.add(
          BankInfos(
            code: element.code,
            id: element.id,
            name: element.name,
          ),
        );
      }
      foundChapter.value = _flutterWaveBanksModel.data.bankInfo;
      selectFlutterWaveBankName.value =
          _flutterWaveBanksModel.data.bankInfo.first.name;
      bankNameController.text = _flutterWaveBanksModel.data.bankInfo.first.name;
      bankCode.value = _flutterWaveBanksModel.data.bankInfo.first.name;

      selectFlutterWaveBankCode.value =
          _flutterWaveBanksModel.data.bankInfo.first.code;
      selectFlutterWaveBankId.value =
          _flutterWaveBanksModel.data.bankInfo.first.id;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _flutterWaveBanksModel;
  }

  final _isInsertLoading = false.obs;

  bool get isInsertLoading => _isInsertLoading.value;

  late WithdrawManualInsertModel _moneyOutManualInsertModel;

  WithdrawManualInsertModel get moneyOutManualInsertModel =>
      _moneyOutManualInsertModel;

  // --------------------------- Api function ----------------------------------
  // Manual Payment Get Gateway process function
  Future<WithdrawManualInsertModel> manualPaymentGetGatewaysProcess() async {
    _isInsertLoading.value = true;
    inputFields.clear();
    listImagePath.clear();
    listFieldName.clear();
    inputFieldControllers.clear();
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'gateway': selectedCurrencyAlias.value,
    };

    await ApiServices.withdrawManualInsertApi(body: inputBody).then((value) {
      _moneyOutManualInsertModel = value!;

      final previewData = _moneyOutManualInsertModel.data.paymentInformations;
      enteredAmount = previewData.requestAmount;
      transferFeeAmount = previewData.totalCharge;
      totalCharge = previewData.totalCharge;
      youWillGet = previewData.willGet;
      payableAmount = previewData.requestAmount;

      //-------------------------- Process inputs start ------------------------
      final data = _moneyOutManualInsertModel.data.inputFields;

      for (int item = 0; item < data.length; item++) {
        // make the dynamic controller
        var textEditingController = TextEditingController();
        inputFieldControllers.add(textEditingController);

        // make dynamic input widget
        if (data[item].type.contains('file')) {
          hasFile.value = true;
          inputFields.add(
            Padding(
              padding: const EdgeInsets.only(bottom: 8.0),
              child: ManualPaymentImageWidgetForMoneyOut(
                labelName: data[item].label,
                fieldName: data[item].name,
              ),
            ),
          );
        } else if (data[item].type.contains('text') ||
            data[item].type.contains('textarea')) {
          inputFields.add(
            Column(
              children: [
                PrimaryInputWidget(
                  paddings: EdgeInsets.only(
                      left: Dimensions.widthSize,
                      right: Dimensions.widthSize,
                      top: Dimensions.heightSize * 0.5),
                  controller: inputFieldControllers[item],
                  label: data[item].label,
                  hint: data[item].label,
                  isValidator: data[item].required,
                  inputFormatters: [
                    LengthLimitingTextInputFormatter(
                        int.parse(data[item].validation.max.toString())),
                  ],
                ),
              ],
            ),
          );
        }
        // final selectedIDType = "".obs;
        // List<IdTypeModel> idTypeList = [];
        else if (data[item].type.contains('select')) {
          hasFile.value = true;
          selectedIDType.value = data[item].validation.options.first.toString();
          inputFieldControllers[item].text = selectedIDType.value;
          for (var element in data[item].validation.options) {
            idTypeList.add(IdTypeModel(element, element));
          }
          inputFields.add(
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Obx(() => CustomDropDown<IdTypeModel>(
                    items: idTypeList,
                    title: data[item].label,
                    hint: selectedIDType.value.isEmpty
                        ? Strings.selectType
                        : selectedIDType.value,
                    onChanged: (value) {
                      selectedIDType.value = value!.title;
                    },
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.paddingHorizontalSize * 0.25,
                    ),
                    titleTextColor:
                        CustomColor.primaryLightTextColor.withOpacity(.2),
                    borderEnable: true,
                    dropDownFieldColor: Colors.transparent,
                    dropDownIconColor:
                        CustomColor.primaryLightTextColor.withOpacity(.2))),
                verticalSpace(Dimensions.marginBetweenInputBox * .8),
              ],
            ),
          );
        }
      }

      //-------------------------- Process inputs end --------------------------
      _isInsertLoading.value = false;
      goToAddMoneyPreviewScreen();
      update();
    }).catchError((onError) {
      _isInsertLoading.value = false;
      log.e(onError);
    });

    update();
    return _moneyOutManualInsertModel;
  }

  // ---------------------------- manualPaymentProcess -------------------------

  final _isConfirmManualLoading = false.obs;

  bool get isConfirmManualLoading => _isConfirmManualLoading.value;

  late CommonSuccessModel _manualPaymentConfirmModel;

  CommonSuccessModel get manualPaymentConfirmModel =>
      _manualPaymentConfirmModel;

  Future<CommonSuccessModel> manualPaymentProcess() async {
    _isConfirmManualLoading.value = true;
    Map<String, String> inputBody = {
      'trx': moneyOutManualInsertModel.data.paymentInformations.trx,
    };

    final data = moneyOutManualInsertModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
        debugPrint("----------------------");
        debugPrint(listFieldName.toString());
        debugPrint(data[i].name);
      }
    }

    await ApiServices.manualPaymentConfirmApiForWithdraw(
      body: inputBody,
      fieldList: listFieldName,
      pathList: listImagePath,
    ).then((value) {
      _manualPaymentConfirmModel = value!;
      _isConfirmManualLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isConfirmManualLoading.value = false;
    update();
    return _manualPaymentConfirmModel;
  }

  late WithdrawFlutterWaveInsertModel _withdrawFlutterwaveInsertModel;

  WithdrawFlutterWaveInsertModel get withdrawFlutterwaveInsertModel =>
      _withdrawFlutterwaveInsertModel;

  // Automatic Payment Get Gateway process function
  Future<WithdrawFlutterWaveInsertModel>
      automaticPaymentFlutterwaveInsertProcess() async {
    inputFieldControllersFlutterWave.clear();
    inputFieldsFlutterWave.clear();
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'gateway': selectedCurrencyAlias.value,
    };

    await ApiServices.withdrawAutomaticFluuerwaveInsertApi(body: inputBody)
        .then((value) {
      _withdrawFlutterwaveInsertModel = value!;

      final previewData =
          _withdrawFlutterwaveInsertModel.data.paymentInformations;
      enteredAmount = previewData.requestAmount;
      transferFeeAmount = previewData.totalCharge;
      totalCharge = previewData.totalCharge;
      youWillGet = previewData.willGet;
      payableAmount = previewData.payable;
      isBranch.value = _withdrawFlutterwaveInsertModel.data.branchAvailable;

      //-------------------------- Process inputs start ------------------------
      final gatewayCurrencyCode =
          _withdrawFlutterwaveInsertModel.data.gatewayCurrencyCode;
      final data = _withdrawFlutterwaveInsertModel.data.inputFields;
      RxList<Option> branchDropdownList = <Option>[].obs;

      for (int item = 0; item < data.length; item++) {
        // make the dynamic controller
        var textEditingController = TextEditingController();
        inputFieldControllersFlutterWave.add(textEditingController);
        // make dynamic input widget
        if (data[item].type.contains('file')) {
          hasFileFlutterWave.value = true;
          inputFieldsFlutterWave.add(
            Padding(
              padding: const EdgeInsets.only(bottom: 8.0),
              child: ManualPaymentImageWidgetForMoneyOut(
                labelName: data[item].label,
                fieldName: data[item].name,
              ),
            ),
          );
        } else if (data[item].type.contains('text') ||
            data[item].type.contains('textarea') ||
            data[item].type.contains('number')) {
          inputFieldsFlutterWave.add(
            Column(
              children: [
                PrimaryInputWidget(
                  paddings: EdgeInsets.only(
                    left: Dimensions.widthSize,
                    right: Dimensions.widthSize,
                    top: Dimensions.heightSize * 0.5,
                  ),
                  controller: inputFieldControllersFlutterWave[item],
                  label: data[item].label,
                  hint: data[item].label,
                  isValidator: data[item].required,
                  // inputFormatters: [
                  //   LengthLimitingTextInputFormatter(
                  //       int.parse(data[item].validation.max.toString())),
                  // ],
                ),
                verticalSpace(Dimensions.marginBetweenInputBox * 0.8),
              ],
            ),
          );
        } else if (data[item].type.contains('select')) {
          hasFileFlutterWave.value = true;

          Rx<Option?> selectedOption = Rx<Option?>(null);
          RxList<Option> dropdownList = <Option>[].obs;

          if (data[item].options.isNotEmpty) {
            dropdownList.assignAll(
              data[item].options.map(
                    (element) => Option(
                      id: element.id,
                      code: element.code ?? '',
                      name: element.name,
                    ),
                  ),
            );
          }
          if (data[item].name == "bank_name") {
            selectedOption.value = dropdownList.first;
            if (gatewayCurrencyCode == "USD" ||
                gatewayCurrencyCode == "EUR" ||
                gatewayCurrencyCode == "GBP") {
              inputFieldControllersFlutterWave[item].text =
                  selectedOption.value?.name ?? '';
            } else {
              inputFieldControllersFlutterWave[item].text =
                  selectedOption.value?.code ?? '';
            }

            getFlutterWaveBanksBranch(
              selectedOption.value!.id.toString(),
              branchDropdownList,
            );
          }

          // Bank Name Dropdown
          if (data[item].name == "bank_name") {
            inputFieldsFlutterWave.add(
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Obx(
                    () => CustomDropDown<Option>(
                      items: dropdownList,
                      title: data[item].label,
                      titleStyle: CustomStyle.darkHeading3TextStyle,
                      hint: selectedOption.value?.name ?? Strings.selectType,
                      onChanged: (value) {
                        if (value != null) {
                          selectedOption.value = value;
                          if (gatewayCurrencyCode == "USD") {
                            inputFieldControllersFlutterWave[item].text =
                                selectedOption.value?.name ?? '';
                          } else {
                            inputFieldControllersFlutterWave[item].text =
                                selectedOption.value?.code ?? '';
                          }
                          branchDropdownList.clear();

                          getFlutterWaveBanksBranch(
                            value.id.toString(),
                            branchDropdownList,
                          );
                        }
                      },
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingHorizontalSize * 0.25,
                      ),
                      titleTextColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                      borderEnable: true,
                      dropDownFieldColor: Colors.transparent,
                      dropDownIconColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                    ),
                  ),
                  verticalSpace(Dimensions.marginBetweenInputBox * 0.8),
                ],
              ),
            );
          }

          // Branch Code Dropdown
          else if (data[item].name == "branch_code") {
            inputFieldsFlutterWave.add(
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Obx(
                    () => CustomDropDown<Option>(
                      items: branchDropdownList,
                      title: data[item].label,
                      hint: selectedOption.value?.name ?? Strings.selectType,
                      onChanged: (value) {
                        if (value != null) {
                          selectedOption.value = value;
                          inputFieldControllersFlutterWave[item].text =
                              value.code;
                        }
                      },
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingHorizontalSize * 0.25,
                      ),
                      titleTextColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                      borderEnable: true,
                      dropDownFieldColor: Colors.transparent,
                      dropDownIconColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                    ),
                  ),
                  verticalSpace(Dimensions.marginBetweenInputBox * 0.8),
                ],
              ),
            );
          } else {
            inputFieldsFlutterWave.add(
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Obx(
                    () => CustomDropDown<Option>(
                      items: dropdownList,
                      title: data[item].label,
                      hint: selectedOption.value?.name ??
                          Get.find<LanguageController>()
                              .getTranslation(Strings.selectType),
                      onChanged: (value) {
                        if (value != null) {
                          selectedOption.value = value;

                          inputFieldControllersFlutterWave[item].text =
                              selectedOption.value?.name ?? '';
                        }
                      },
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingHorizontalSize * 0.25,
                      ),
                      titleTextColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                      borderEnable: true,
                      dropDownFieldColor: Colors.transparent,
                      dropDownIconColor:
                          CustomColor.primaryLightTextColor.withOpacity(0.2),
                    ),
                  ),
                  verticalSpace(Dimensions.marginBetweenInputBox * 0.8),
                ],
              ),
            );
          }
          // Automatically select the first available branch after fetching
          ever(
            branchDropdownList,
            (_) {
              if (branchDropdownList.isNotEmpty) {
                if (data[item].name == "branch_code") {
                  selectedOption.value = branchDropdownList.first;
                  inputFieldControllersFlutterWave[item].text =
                      selectedOption.value!.code;
                }
              }
            },
          );
        }
      }
      _isInsertLoading.value = false;
      getFlutterWaveBanks().then((value) => goToAddMoneyPreviewScreen());

      update();
    }).catchError((onError) {
      _isInsertLoading.value = false;
      log.e(onError);
    });

    update();
    return _withdrawFlutterwaveInsertModel;
  }

  Future<CommonSuccessModel> flutterwavePaymentProcess() async {
    _isConfirmManualLoading.value = true;
    Map<String, String> inputBody = {
      'trx': withdrawFlutterwaveInsertModel.data.paymentInformations.trx,
    };

    final data = _withdrawFlutterwaveInsertModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllersFlutterWave[i].text;
      }
    }
    await ApiServices.withdrawFluuerwaveConfirmApiApi(body: inputBody)
        .then((value) {
      _manualPaymentConfirmModel = value!;

      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isConfirmManualLoading.value = false;
    update();
    return _manualPaymentConfirmModel;
  }

  goToAddMoneyPreviewScreen() {
    Get.toNamed(Routes.withdrawPreviewScreen);
  }

  goToAddMoneyCongratulationScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  RxString selectItem = ''.obs;
  List<String> keyboardItemList = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '.',
    '0',
    '<'
  ];

  inputItem(int index) {
    return InkWell(
      overlayColor: WidgetStateProperty.all(Colors.transparent),
      onLongPress: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.clear();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        }
      },
      onTap: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.removeLast();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        } else {
          if (totalAmount.contains('.') && index == 9) {
            return;
          } else {
            totalAmount.add(keyboardItemList[index]);
            amountTextController.text = totalAmount.join('');
            debugPrint(totalAmount.join(''));
          }
        }
      },
      child: Center(
        child: CustomTitleHeadingWidget(
          text: keyboardItemList[index],
          style: Get.isDarkMode
              ? CustomStyle.darkHeading2TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize3 * 2,
                )
              : CustomStyle.darkHeading2TextStyle.copyWith(
                  color: CustomColor.primaryLightColor,
                  fontSize: Dimensions.headingTextSize3 * 2,
                ),
        ),
      ),
    );
  }

  final accountNumberController = TextEditingController();
  final bankNameController = TextEditingController();
  RxBool isSearchEnable = false.obs;
  RxBool isBranchSearchEnable = false.obs;
  RxBool isButtonEnable = false.obs;

  RxString bankCode = "".obs;
  Rx<List<BankInfos>> foundChapter = Rx<List<BankInfos>>([]);

  void filterTransaction(String? value) {
    List<BankInfos> results = [];
    if (value!.isEmpty) {
      results = _flutterWaveBanksModel.data.bankInfo;
    } else {
      results = _flutterWaveBanksModel.data.bankInfo
          .where((element) => element.name
              .toString()
              .toLowerCase()
              .contains(value.toLowerCase()))
          .toList();
    }

    if (results.isEmpty) {
      foundChapter.value = [
        BankInfos(name: Strings.noBankFound, id: 0, code: ''),
      ];
    } else {
      foundChapter.value = results;
    }
  }

  late FlutterwaveAccountCheckModel _flutterwaveAccountCheckModel;

  FlutterwaveAccountCheckModel get flutterwaveAccountCheckModel =>
      _flutterwaveAccountCheckModel;

  Future<FlutterwaveAccountCheckModel> cheackUser(value) async {
    // _isConfirmManualLoading.value = true;
    Map<String, String> inputBody = {
      // 'trx': withdrawFlutterwaveInsertModel.data.paymentInformations.trx,
      'bank_name': bankCode.value,
      'account_number': accountNumberController.text,
    };

    await ApiServices.flutterwaveAccountCheackerApi(body: inputBody)
        .then((value) {
      _flutterwaveAccountCheckModel = value!;

      isButtonEnable.value = true;
      CustomSnackBar.success(
          "Hello ${_flutterwaveAccountCheckModel.data.bankInfo.accountName}");
      update();
    }).catchError((onError) {
      isButtonEnable.value = false;
      log.e(onError);
    });
    // _isConfirmManualLoading.value = false;
    update();
    return _flutterwaveAccountCheckModel;
  }

  // updateExchangeRate() {
  //   exchangeRate.value = gateWayCurrencyRate.value /
  //       double.parse(selectMainWallet.value!.currency.rate.toString());
  //   updateLimit();
  // }

  // updateLimit() {
  //   fee.value = fixedCharge.value / exchangeRate.value;
  //   minLimit.value = min.value / exchangeRate.value;
  //   maxLimit.value = max.value / exchangeRate.value;

  //   dailyLimit.value = dailyLimit.value / exchangeRate.value;
  //   monthlyLimit.value = monthlyLimit.value / exchangeRate.value;
  // }

  Rx<List<BankBranch>> branch = Rx<List<BankBranch>>([]);

  final _isBranchLoading = false.obs;
  bool get isBranchLoading => _isBranchLoading.value;

  late BankBranchesModel _bankBranchesModel;
  BankBranchesModel get bankBranchesModel => _bankBranchesModel;

  Future<BankBranchesModel?> getFlutterWaveBanksBranch(
      String id, RxList<Option> dropdownList) async {
    return RequestProcess().request<BankBranchesModel>(
      fromJson: BankBranchesModel.fromJson,
      apiEndpoint:
          "${ApiEndpoint.flutterWaveBanksBranchURL}trx=${withdrawFlutterwaveInsertModel.data.paymentInformations.trx}&bank_id=$id",
      isLoading: _isBranchLoading,
      onSuccess: (value) {
        _bankBranchesModel = value!;
        var data = _bankBranchesModel.data.bankBranches;
        branch.value = data;
        branchNameController.text =
            data.isNotEmpty ? data.first.branchName : '';
        selectFlutterWaveBankBranchCode.value =
            data.isNotEmpty ? data.first.branchCode : '';

        // Use assignAll to update the list reactively
        dropdownList.assignAll(data
            .map((item) => Option(
                  id: item.id,
                  code: item.branchCode,
                  name: item.branchName,
                ))
            .toList());

        update();
      },
    );
  }

  void filterBranch(String? value) {
    List<BankBranch> results = [];
    if (value!.isEmpty) {
      results = _bankBranchesModel.data.bankBranches;
    } else {
      results = _bankBranchesModel.data.bankBranches
          .where((element) => element.branchName
              .toString()
              .toLowerCase()
              .contains(value.toLowerCase()))
          .toList();
    }

    if (results.isEmpty) {
      branch.value = [
        BankBranch(
          id: 1,
          branchCode: '',
          branchName: '',
          swiftCode: '',
          bic: '',
          bankId: 1,
        ),
      ];
    } else {
      branch.value = results;
    }
  }

  final _isBankAccountCheckLoading = false.obs;
  bool get isBankAccountCheckLoading => _isBankAccountCheckLoading.value;

  late BankAccountCheckModel _bankAccountCheckModel;
  BankAccountCheckModel get bankAccountCheckModel => _bankAccountCheckModel;

  Future<BankAccountCheckModel?> checkAccountInfo() async {
    return RequestProcess().request<BankAccountCheckModel>(
      fromJson: BankAccountCheckModel.fromJson,
      apiEndpoint: ApiEndpoint.addMoneyInfoURL,
      isLoading: _isBankAccountCheckLoading,
      onSuccess: (value) {
        _bankAccountCheckModel = value!;
      },
    );
  }
}
